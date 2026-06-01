<?php

namespace App\Support;

use App\Models\AccountType;
use App\Models\Game;
use App\Models\Region;
use App\Models\Section;
use App\Models\Skin;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Parses WooCommerce category strings into our normalized taxonomy IDs.
 *
 * Real WC category patterns from 99accs export:
 *
 *   Valorant:  "🔹 Valorant NA > NA - Skins + Mail Access, 🔹 Valorant NA"
 *              "🔹 Valorant EU > EU – Inactive Account Verified, 🔹 Valorant EU"
 *              Parent encodes: [emoji] Valorant [Region]
 *              Child  encodes: [REGION] - [Account Type / Feature]
 *
 *   Fortnite:  "🔻 Fortnite FA > Fortnite Random Skins + Email Access, 🔻 Fortnite FA"
 *              "🔻 Fortnite NFA > Inactive-Exclusive, 🔻 Fortnite NFA, 🔻 Fortnite NFA+FA"
 *              Parent encodes: [emoji] Fortnite [FA|NFA]  (no region — Fortnite is global)
 *              Child  encodes: section / feature description
 *
 *   LoL:       "LOL-EU&NE > LOL EU&NE - Exclusive Mail Access, LOL-EU&NE"
 *              "EU West > Champ-EU West"
 *              "LOL EXC > TURKEY EXC MAIL ACCESS, LOL EXC"
 *              Parent encodes: LOL-[Region] | EU West | LOL EXC
 *              Child  encodes: [Section]-[Region] or [REGION] EXC [FEATURE]
 *
 * Strategy:
 *   1. Split on ',' → individual paths; split each on '>' → [parent, child?]
 *   2. Strip emojis; detect game from parents first
 *   3. Extract region from parent token (Valorant) or child token (LoL EXC)
 *   4. Extract account type and section from child + game context
 *   5. Auto-create any missing taxonomy terms and log them
 */
class WooCommerceCategoryMapper
{
    // normalized_key → id (loaded once from DB per job instance)
    private array $games        = [];
    private array $accountTypes = [];  // "slug" and "game_id:slug" keys
    private array $regions      = [];  // "code" and "slug" keys (lowercase)
    private array $sections     = [];  // "slug" and "game_id:slug" keys
    private array $skins        = [];  // "slug" keys

    // Items auto-created during this parse run (returned for job-level logging)
    private array $autoCreated = [];

    public function __construct()
    {
        $this->loadTaxonomy();
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function loadTaxonomy(): void
    {
        foreach (Game::all() as $g) {
            $this->games[$this->norm($g->slug)] = $g->id;
            $this->games[$this->norm($g->name)] = $g->id;
        }

        foreach (AccountType::all() as $a) {
            $this->accountTypes[$this->norm($a->slug)]                       = $a->id;
            $this->accountTypes[$this->norm($a->name)]                       = $a->id;
            $this->accountTypes["{$a->game_id}:{$this->norm($a->slug)}"]     = $a->id;
        }

        foreach (Region::all() as $r) {
            if ($r->code)  $this->regions[strtolower(trim($r->code))]  = $r->id;
            $this->regions[$this->norm($r->slug)] = $r->id;
            $this->regions[$this->norm($r->name)] = $r->id;
        }

        foreach (Section::all() as $s) {
            $this->sections[$this->norm($s->slug)]                       = $s->id;
            $this->sections[$this->norm($s->label)]                      = $s->id;
            $this->sections["{$s->game_id}:{$this->norm($s->slug)}"]     = $s->id;
        }

        foreach (Skin::all() as $s) {
            $this->skins[$this->norm($s->slug)] = $s->id;
            $this->skins[$this->norm($s->name)] = $s->id;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   game_id: int|null,
     *   account_type_id: int|null,
     *   section_id: int|null,
     *   region_ids: int[],
     *   skin_ids: int[],
     *   feature_badges: array,
     *   _auto_created: string[],
     * }
     */
    public function parse(string $categoriesCsv, string $productName = '', string $sku = ''): array
    {
        $this->autoCreated = [];

        $paths = $this->parsePaths($categoriesCsv);

        $gameId        = $this->detectGame($paths);
        $regionIds     = $this->detectRegions($paths, $gameId, $productName, $sku);
        $accountTypeId = $this->detectAccountType($paths, $gameId);
        $sectionId     = $this->detectSection($paths, $gameId, $accountTypeId);

        $allText = strtolower(implode(' ', array_merge(
            array_column($paths, 0),
            array_column($paths, 1),
            [$productName, $sku],
        )));

        return [
            'game_id'         => $gameId,
            'account_type_id' => $accountTypeId,
            'section_id'      => $sectionId,
            'region_ids'      => array_values(array_unique(array_filter($regionIds))),
            'skin_ids'        => $this->detectSkins($allText),
            'feature_badges'  => $this->detectFeatureBadges($allText, $productName),
            '_auto_created'   => $this->autoCreated,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Path parsing
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Splits "A > B, C > D" into [[clean(A), clean(B)], [clean(C), clean(D)]].
     * Emoji and surrounding whitespace are stripped from each segment.
     */
    private function parsePaths(string $csv): array
    {
        $paths = [];
        foreach (array_filter(array_map('trim', explode(',', $csv))) as $cat) {
            $levels = array_values(array_filter(
                array_map(fn(string $s) => trim($this->stripEmoji($s)), explode('>', $cat))
            ));
            if ($levels) {
                $paths[] = [$levels[0] ?? '', $levels[1] ?? ''];
            }
        }
        return $paths;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Game detection
    // ─────────────────────────────────────────────────────────────────────────

    private function detectGame(array $paths): ?int
    {
        foreach ($paths as [$parent]) {
            $p = strtolower($parent);
            if (str_contains($p, 'valorant'))                                  return $this->games['valorant'] ?? null;
            if (str_contains($p, 'fortnite'))                                  return $this->games['fortnite'] ?? null;
            if (str_contains($p, 'lol') || str_contains($p, 'league'))        return $this->games['legends']  ?? null;
            // "EU West" parent without "Valorant" = LoL EU West sub-category
            if (str_contains($p, 'eu west'))                                   return $this->games['legends']  ?? null;
        }
        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Region detection
    // ─────────────────────────────────────────────────────────────────────────

    private function detectRegions(array $paths, ?int $gameId, string $name, string $sku): array
    {
        $ids = [];

        foreach ($paths as [$parent, $child]) {
            $p = strtolower($parent);
            $c = strtolower($child);

            // ── Valorant: parent = "Valorant NA/EU/AP/BR/Latam" ─────────────
            if (str_contains($p, 'valorant')) {
                // Strip "valorant" and grab the trailing region token
                $region = trim(preg_replace('/valorant\s*/i', '', $parent));
                $ids[]  = $this->resolveRegionByToken($region);
                continue;
            }

            // ── Fortnite: no region ──────────────────────────────────────────
            if (str_contains($p, 'fortnite')) {
                continue;
            }

            // ── LoL: parent = "LOL-EU&NE" | "LOL-NA" | "EU West" | "LOL EXC" ─
            if (str_contains($p, 'lol') || str_contains($p, 'eu west')) {
                if (str_contains($p, 'eu west') || str_contains($p, 'eu&we')) {
                    $ids[] = $this->resolveRegionByCode('euw', 'EUW', '🇪🇺', 'euw');
                } elseif (preg_match('/lol-na\b/i', $p) || str_contains($p, 'lol-na')) {
                    $ids[] = $this->resolveRegionByCode('na', 'NA', '🇺🇸', 'na');
                } elseif (preg_match('/lol-eu/i', $p)) {
                    // LOL-EU&NE, LOL-EUW, etc.
                    if (str_contains($p, 'west') || str_contains($p, 'euw')) {
                        $ids[] = $this->resolveRegionByCode('euw', 'EUW', '🇪🇺', 'euw');
                    } else {
                        $ids[] = $this->resolveRegionByCode('eu', 'EU', '🇪🇺', 'eu');
                    }
                } elseif (str_contains($p, 'lol exc')) {
                    // Region is encoded in the child: "LAN EXC", "LAS EXC", "TURKEY EXC", etc.
                    $ids[] = $this->resolveRegionFromLolExcChild($c);
                }
                continue;
            }
        }

        // Fallback: scan product name + SKU only if nothing found from categories
        if (empty(array_filter($ids))) {
            $fallback = strtolower(trim($name . ' ' . $sku));
            if ($fallback) {
                $ids = $this->detectRegionsFromText($fallback);
            }
        }

        return $ids;
    }

    private function resolveRegionFromLolExcChild(string $child): ?int
    {
        if (str_contains($child, 'lan '))                          return $this->resolveRegionByCode('latam', 'LATAM', '🌎', 'latam');
        if (str_contains($child, 'las '))                          return $this->resolveRegionByCode('las',   'LAS',   '🌎', 'las');
        if (str_contains($child, 'sea ') || str_contains($child, 'southeast') || str_contains($child, 'vietnam')) {
                                                                    return $this->resolveRegionByCode('apac',  'APAC',  '🌏', 'apac');
        }
        if (str_contains($child, 'turkey'))                        return $this->resolveRegionByCode('tr',    'TR',    '🇹🇷', 'tr');
        if (str_contains($child, 'eu west') || str_contains($child, 'eu&we') || str_contains($child, 'euw')) {
                                                                    return $this->resolveRegionByCode('euw',   'EUW',   '🇪🇺', 'euw');
        }
        if (preg_match('/\bna\b/u', $child))                       return $this->resolveRegionByCode('na',    'NA',    '🇺🇸', 'na');
        if (preg_match('/\beu\b/u', $child))                       return $this->resolveRegionByCode('eu',    'EU',    '🇪🇺', 'eu');
        return null;
    }

    /**
     * Resolve a region from a raw token like "NA", "EU", "AP", "BR", "Latam".
     */
    private function resolveRegionByToken(string $token): ?int
    {
        $t = strtolower(trim($token));

        return match (true) {
            $t === 'na' || str_contains($t, 'north america')        => $this->resolveRegionByCode('na',    'NA',    '🇺🇸', 'na'),
            $t === 'eu' || str_contains($t, 'europe')               => $this->resolveRegionByCode('eu',    'EU',    '🇪🇺', 'eu'),
            $t === 'ap' || $t === 'asia pacific'                    => $this->resolveRegionByCode('ap',    'AP',    '🌏', 'ap'),
            $t === 'br' || str_contains($t, 'brazil')               => $this->resolveRegionByCode('br',    'BR',    '🇧🇷', 'br'),
            $t === 'latam' || str_contains($t, 'latin')             => $this->resolveRegionByCode('latam', 'LATAM', '🌎', 'latam'),
            $t === 'tr' || str_contains($t, 'turkey')               => $this->resolveRegionByCode('tr',    'TR',    '🇹🇷', 'tr'),
            $t === 'las'                                             => $this->resolveRegionByCode('las',   'LAS',   '🌎', 'las'),
            str_contains($t, 'eu west') || $t === 'euw'             => $this->resolveRegionByCode('euw',   'EUW',   '🇪🇺', 'euw'),
            default                                                  => null,
        };
    }

    /** Last-resort keyword scan (product name / SKU fallback). */
    private function detectRegionsFromText(string $text): array
    {
        $ids = [];
        if (str_contains($text, 'eu west') || preg_match('/\beuw\b/u', $text))  $ids[] = $this->resolveRegionByCode('euw',   'EUW',   '🇪🇺', 'euw');
        if (preg_match('/\bna\b/u', $text))                                       $ids[] = $this->resolveRegionByCode('na',    'NA',    '🇺🇸', 'na');
        if (preg_match('/\bbr\b/u', $text))                                       $ids[] = $this->resolveRegionByCode('br',    'BR',    '🇧🇷', 'br');
        if (preg_match('/\bap\b/u', $text))                                       $ids[] = $this->resolveRegionByCode('ap',    'AP',    '🌏', 'ap');
        if (preg_match('/\b(latam|lan)\b/u', $text))                              $ids[] = $this->resolveRegionByCode('latam', 'LATAM', '🌎', 'latam');
        if (preg_match('/\btr\b/u', $text) || str_contains($text, 'turkey'))     $ids[] = $this->resolveRegionByCode('tr',    'TR',    '🇹🇷', 'tr');
        if (preg_match('/\blas\b/u', $text))                                      $ids[] = $this->resolveRegionByCode('las',   'LAS',   '🌎', 'las');
        if (! in_array($this->regions['euw'] ?? -1, $ids, true) && preg_match('/\beu\b/u', $text)) {
            $ids[] = $this->resolveRegionByCode('eu', 'EU', '🇪🇺', 'eu');
        }
        return $ids;
    }

    private function resolveRegionByCode(string $slug, string $code, string $flag, string $classModifier): ?int
    {
        $lowerCode = strtolower($code);
        if (isset($this->regions[$slug]))      return $this->regions[$slug];
        if (isset($this->regions[$lowerCode])) return $this->regions[$lowerCode];

        $region = Region::firstOrCreate(
            ['slug' => $slug],
            ['name' => $code, 'code' => $code, 'flag' => $flag, 'class_modifier' => $classModifier, 'sort_order' => 99],
        );
        $this->regions[$slug]      = $region->id;
        $this->regions[$lowerCode] = $region->id;
        $this->autoCreated[]       = "region:{$slug}";

        return $region->id;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Account type detection
    // ─────────────────────────────────────────────────────────────────────────

    private function detectAccountType(array $paths, ?int $gameId): ?int
    {
        foreach ($paths as [$parent, $child]) {
            $p = strtolower($parent);
            $c = strtolower($child);

            // ── Fortnite: account type is in the parent ──────────────────────
            if (str_contains($p, 'fortnite')) {
                // NFA + child "Inactive-Exclusive" → inactive_exclusive
                if (str_contains($p, 'nfa') && str_contains($c, 'inactive')) {
                    return $this->resolveAccountType('inactive_exclusive', 'Inactive Exclusive', $gameId);
                }
                // FA = Full Access → our "verified"
                if (str_contains($p, ' fa') || str_ends_with(trim($p), 'fa')) {
                    return $this->resolveAccountType('verified', 'Verified', $gameId);
                }
                // NFA → our "nfa"
                if (str_contains($p, 'nfa')) {
                    return $this->resolveAccountType('nfa', 'NFA', $gameId);
                }
            }

            // ── Valorant: account type is in the child ───────────────────────
            if (str_contains($p, 'valorant') && $c !== '') {
                if (str_contains($c, 'inactive account'))                      return $this->resolveAccountType('inactive_exclusive', 'Inactive Exclusive', $gameId);
                if (str_contains($c, 'guaranteed skin verified'))              return $this->resolveAccountType('verified', 'Verified', $gameId);
                if (str_contains($c, 'skins verified'))                        return $this->resolveAccountType('verified', 'Verified', $gameId);
                if (str_contains($c, 'rank'))                                  return $this->resolveAccountType('verified', 'Verified', $gameId);
                if (str_contains($c, 'guaranteed skin'))                       return $this->resolveAccountType('verified', 'Verified', $gameId);
                if (str_contains($c, 'skins + mail') || str_contains($c, 'skins+mail')) return $this->resolveAccountType('standard', 'Standard', $gameId);
                if (str_contains($c, 'with description'))                      return $this->resolveAccountType('standard', 'Standard', $gameId);
                if (str_contains($c, 'mail access') || str_contains($c, 'email access')) return $this->resolveAccountType('standard', 'Standard', $gameId);
            }

            // ── LoL ──────────────────────────────────────────────────────────
            if ((str_contains($p, 'lol') || str_contains($p, 'eu west')) && $c !== '') {
                // "LOL EXC" parent = exclusive (inactive) accounts
                if (str_contains($p, 'exc') || str_contains($c, 'exclusive mail access') || str_contains($c, 'exc ')) {
                    return $this->resolveAccountType('inactive_exclusive', 'Inactive Exclusive', $gameId);
                }
                // Standard sub-sections (champ, rank, skins) = verified accounts
                if (str_contains($c, 'champ') || str_contains($c, 'rank') || str_contains($c, 'skin')) {
                    return $this->resolveAccountType('verified', 'Verified', $gameId);
                }
            }
        }

        return null;
    }

    private function resolveAccountType(string $slug, string $name, ?int $gameId): ?int
    {
        $gameKey = $gameId ? "{$gameId}:{$this->norm($slug)}" : null;
        if ($gameKey && isset($this->accountTypes[$gameKey]))  return $this->accountTypes[$gameKey];
        if (isset($this->accountTypes[$this->norm($slug)]))    return $this->accountTypes[$this->norm($slug)];

        if (! $gameId) return null;

        $at = AccountType::firstOrCreate(
            ['slug' => $slug, 'game_id' => $gameId],
            ['name' => $name, 'sort_order' => 99],
        );
        $this->accountTypes[$this->norm($slug)]       = $at->id;
        $this->accountTypes["{$gameId}:{$this->norm($slug)}"] = $at->id;
        $this->autoCreated[] = "account_type:{$slug}@game{$gameId}";

        return $at->id;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Section detection
    // ─────────────────────────────────────────────────────────────────────────

    private function detectSection(array $paths, ?int $gameId, ?int $accountTypeId): ?int
    {
        foreach ($paths as [$parent, $child]) {
            $p = strtolower($parent);
            $c = strtolower($child);

            // ── Fortnite sections from child ─────────────────────────────────
            if (str_contains($p, 'fortnite')) {
                if (str_contains($c, 'random skin'))                           return $this->resolveSection('nfa_random',          'NFA Random Skins',       $gameId);
                if (str_contains($c, 'guarant') && str_contains($c, 'skin'))   return $this->resolveSection('nfa_guaranteed',       'NFA Guaranteed Skins',   $gameId);
                if (str_contains($c, 'inactive') || str_contains($c, 'exclusive')) return $this->resolveSection('inactive_exclusive', 'Inactive Exclusive',   $gameId);
                // "Paid Skins" and "With Description" don't map to a specific section — leave null
            }

            // ── Valorant sections from child ─────────────────────────────────
            if (str_contains($p, 'valorant')) {
                if (str_contains($c, 'inactive account'))                      return $this->resolveSection('inactive_exclusive', 'Inactive Exclusive', $gameId);
                if (str_contains($c, 'guaranteed skin verified') || str_contains($c, 'skins verified')) {
                                                                                return $this->resolveSection('verified',           'Verified',            $gameId);
                }
            }

            // ── LoL sections from parent / child region context ───────────────
            if (str_contains($p, 'eu west') || str_contains($c, 'eu west'))   return $this->resolveSection('euw', 'EUW', $gameId);
            if (str_contains($p, 'lol exc')) {
                $c2 = strtolower($child);
                if (str_contains($c2, 'turkey'))  return $this->resolveSection('tr',    'TR',    $gameId);
                if (preg_match('/\blas\b/u', $c2))  return $this->resolveSection('las',   'LAS',   $gameId);
            }
        }

        return null;
    }

    private function resolveSection(string $slug, string $label, ?int $gameId): ?int
    {
        $gameKey = $gameId ? "{$gameId}:{$this->norm($slug)}" : null;
        if ($gameKey && isset($this->sections[$gameKey]))  return $this->sections[$gameKey];
        if (isset($this->sections[$this->norm($slug)]))    return $this->sections[$this->norm($slug)];

        if (! $gameId) return null;

        $section = Section::firstOrCreate(
            ['slug' => $slug, 'game_id' => $gameId],
            ['label' => $label, 'sort_order' => 99],
        );
        $this->sections[$this->norm($slug)]                       = $section->id;
        $this->sections["{$gameId}:{$this->norm($slug)}"]         = $section->id;
        $this->autoCreated[] = "section:{$slug}@game{$gameId}";

        return $section->id;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Skin tags
    // ─────────────────────────────────────────────────────────────────────────

    private function detectSkins(string $text): array
    {
        $ids = [];

        if (str_contains($text, 'random skin')) {
            $id = $this->resolveOrCreateSkin('random-skins', 'Random Skins');
            if ($id) $ids[] = $id;
        }
        if (str_contains($text, 'guaranteed skin') || str_contains($text, 'guaranted skin')) {
            $id = $this->resolveOrCreateSkin('guaranteed-skins', 'Guaranteed Skins');
            if ($id) $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }

    private function resolveOrCreateSkin(string $slug, string $name): ?int
    {
        if (isset($this->skins[$this->norm($slug)])) return $this->skins[$this->norm($slug)];

        $skin = Skin::firstOrCreate(['slug' => $slug], ['name' => $name, 'sort_order' => 99]);
        $this->skins[$this->norm($slug)] = $skin->id;
        $this->autoCreated[] = "skin:{$slug}";

        return $skin->id;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Feature badges
    // ─────────────────────────────────────────────────────────────────────────

    private function detectFeatureBadges(string $text, string $productName): array
    {
        $badges = [];

        if (str_contains($text, 'mail access') || str_contains($text, 'email access')) {
            $badges[] = ['icon' => 'mail_access', 'label' => 'Mail Access'];
        }
        if (str_contains($text, 'random skin')) {
            $badges[] = ['icon' => 'random_skins', 'label' => 'Random Skins'];
        }
        if (str_contains($text, 'guaranteed skin') || str_contains($text, 'guaranted skin')) {
            $badges[] = ['icon' => 'exclusive_skins', 'label' => 'Guaranteed Skin'];
        }
        if (str_contains($text, 'exclusive') && ! str_contains($text, 'guaranteed')) {
            $badges[] = ['icon' => 'exclusive_skins', 'label' => 'Exclusive Skins'];
        }
        if (str_contains($text, 'champion') || str_contains($text, 'champ-')) {
            $badges[] = ['icon' => 'champions', 'label' => 'Champions'];
        }
        if (preg_match('/(\d+[\s\-–]?\d*)\s*skins?/iu', $productName, $m)) {
            $badges[] = ['icon' => 'skins_count', 'label' => trim($m[1]) . ' Skins'];
        }

        return array_values(array_map(
            fn(array $b, int $i) => ['id' => $i + 1] + $b,
            $badges,
            array_keys($badges),
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Strip emoji codepoints and variation selectors, collapse whitespace.
     * Covers: emoji U+1F000–U+1FFFF, misc symbols U+2600–U+27FF,
     *         variation selectors U+FE00–U+FE0F, zero-width joiner U+200D.
     */
    private function stripEmoji(string $text): string
    {
        $cleaned = preg_replace(
            '/[\x{1F000}-\x{1FFFF}\x{2600}-\x{27FF}\x{FE00}-\x{FE0F}\x{200D}]+/u',
            '',
            $text
        );
        return trim(preg_replace('/\s+/u', ' ', $cleaned ?? $text));
    }

    /** Lowercase + normalize separators for consistent map key lookup. */
    private function norm(string $s): string
    {
        return strtolower(trim(preg_replace('/[\s\-_]+/', ' ', $s)));
    }
}
