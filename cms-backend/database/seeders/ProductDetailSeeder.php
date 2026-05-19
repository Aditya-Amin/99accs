<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductDetailSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding product detail data (descriptions, rich fields, locker/seasons)...');

        $this->seedDescriptionsAndGuarantees();
        $this->seedInactiveExclusive();
        $this->seedNfaInactive();

        $this->command->info('Product detail seeding complete.');
    }

    // ─── Description + Guarantee for every product ────────────────────────────

    private function seedDescriptionsAndGuarantees(): void
    {
        $products = DB::table('products')
            ->join('account_types', 'products.account_type_id', '=', 'account_types.id')
            ->leftJoin('regions', 'products.region_id', '=', 'regions.id')
            ->select(
                'products.id',
                'products.name',
                'regions.code as country_code',
                'regions.slug as region',
                'account_types.slug as account_type',
            )
            ->get();

        foreach ($products as $p) {
            $sections = match ($p->account_type) {
                'verified'           => $this->valorantSimpleDesc($this->skinRange($p->name), $this->regionLabel($p->region)),
                'nfa'                => $this->fortniteDesc($this->skinRange($p->name)),
                'inactive_exclusive' => $this->inactiveExclusiveDesc($this->skinCount($p->name), $this->regionLabel($p->region)),
                'nfa_inactive'       => $this->fortniteDesc($this->skinCount($p->name) . '+'),
                'standard'           => $this->legendsDesc($this->skinCount($p->name), 143, $this->countryLabel($p->country_code)),
                default              => [],
            };

            DB::table('products')->where('id', $p->id)->update([
                'description_sections' => json_encode($sections),
                'guarantee'            => json_encode($this->guarantee()),
            ]);
        }

        $this->command->info('  descriptions & guarantees done.');
    }

    // ─── inactive_exclusive: profile, agents, skins, buddies ──────────────────

    private function seedInactiveExclusive(): void
    {
        $atId = DB::table('account_types')->where('slug', 'inactive_exclusive')->value('id');

        $products = DB::table('products')
            ->where('products.account_type_id', $atId)
            ->leftJoin('regions', 'products.region_id', '=', 'regions.id')
            ->select('products.id', 'products.name', 'regions.slug as region_slug')
            ->get();

        foreach ($products as $p) {
            $count = $this->skinCount($p->name);
            DB::table('products')->where('id', $p->id)->update([
                'agents_detailed' => json_encode($this->agentsDetailed()),
                'agents_count'    => 24,
                'profile_info'    => json_encode($this->profileInfo($p->region_slug ?? 'na', $count)),
                'skin_inventory'  => json_encode($this->skinInventory($count)),
                'skin_filters'    => json_encode($this->skinFilters()),
                'buddy_inventory' => json_encode($this->buddyInventory()),
            ]);
        }

        $this->command->info('  inactive_exclusive rich fields done.');
    }

    // ─── nfa_inactive: account stats, locker, seasons ─────────────────────────

    private function seedNfaInactive(): void
    {
        $atId = DB::table('account_types')->where('slug', 'nfa_inactive')->value('id');

        $products = DB::table('products')
            ->where('account_type_id', $atId)
            ->get(['id', 'name']);

        // Variations so each product feels distinct
        $vars = [
            ['wins' => '5',  'matches' => '24',  'skins' => '72',  'bb' => '18', 'picks' => '4',  'emotes' => '12', 'gliders' => '3',  'excl' => '4',  'level' => 13, 'season' => 8],
            ['wins' => '12', 'matches' => '58',  'skins' => '171', 'bb' => '43', 'picks' => '11', 'emotes' => '27', 'gliders' => '8',  'excl' => '12', 'level' => 42, 'season' => 8],
            ['wins' => '8',  'matches' => '35',  'skins' => '97',  'bb' => '26', 'picks' => '7',  'emotes' => '19', 'gliders' => '5',  'excl' => '7',  'level' => 28, 'season' => 7],
            ['wins' => '21', 'matches' => '89',  'skins' => '204', 'bb' => '61', 'picks' => '16', 'emotes' => '38', 'gliders' => '11', 'excl' => '18', 'level' => 67, 'season' => 8],
        ];

        foreach ($products as $idx => $p) {
            $v = $vars[$idx % count($vars)];
            DB::table('products')->where('id', $p->id)->update([
                'account_level' => json_encode($this->accountLevel($v['level'])),
                'account_stats' => json_encode($this->accountStats($v)),
                'locker'        => json_encode($this->locker()),
                'seasons'       => json_encode($this->seasons($v['season'])),
            ]);
        }

        $this->command->info('  nfa_inactive locker/seasons done.');
    }

    // ─── Helpers — title parsing ───────────────────────────────────────────────

    private function skinRange(string $title): string
    {
        if (preg_match('/(\d+[-–]\d+)\s+Skins/iu', $title, $m)) {
            return $m[1];
        }
        return 'Random';
    }

    private function skinCount(string $title): int
    {
        if (preg_match('/(\d+)\s+[Ss]kins/u', $title, $m)) {
            return (int) $m[1];
        }
        return 50;
    }

    private function guaranteedSkin(string $title): string
    {
        return trim(preg_replace('/\s+Guaranteed\s+Skins?\s*/i', '', $title));
    }

    private function regionLabel(?string $region): string
    {
        return match ($region) {
            'na'    => 'North America',
            'eu'    => 'Europe',
            'apac'  => 'Asia Pacific',
            'latam' => 'Latin America',
            'br'    => 'Brazil',
            default => 'Global',
        };
    }

    private function countryLabel(string $code): string
    {
        return match (strtoupper($code)) {
            'EUW', 'EUNE' => 'Europe West',
            'TR'           => 'Turkey',
            'LAS'          => 'Latin America South',
            'NA'           => 'North America',
            default        => $code,
        };
    }

    // ─── Guarantee (shared) ───────────────────────────────────────────────────

    private function guarantee(): array
    {
        return [
            'title' => '99Accs Guarantee',
            'body'  => 'Your purchase is protected by our 99Accs guarantee. Every account is delivered exactly as described. If you experience any issues within 24 hours of purchase, contact our support team for a full resolution or refund. Our automated delivery system means you receive your account instantly after payment confirmation.',
        ];
    }

    // ─── Description sections ─────────────────────────────────────────────────

    private function valorantSimpleDesc(string $range, string $region): array
    {
        return [
            ['heading' => 'Account Description', 'type' => 'paragraph', 'items' => [
                "Verified {$region} Valorant account with {$range} random weapon skins. The account is in good standing, fully accessible, and ready to play immediately after delivery.",
            ]],
            ['heading' => 'Key Benefits', 'type' => 'list', 'items' => [
                'Instant Delivery After Payment',
                'Full Account Ownership — Email & Password',
                '24/7 Customer Support',
                'Secure & Verified Account — No Bans',
                '100% Legal Purchase',
            ]],
            ['heading' => "What's Included", 'type' => 'list', 'list_class' => 'offer-list', 'items' => [
                'Full Account Credentials (Email + Password)',
                'Full Email Access',
                "{$range} Random Weapon Skins",
                'Account in Good Standing',
                'Current Competitive Rank Preserved',
            ]],
            ['heading' => 'Our Advantages', 'type' => 'list', 'list_class' => 'account-list', 'items' => [
                'Trusted Seller — 5 000+ Successful Sales',
                'Automated Instant Delivery System',
                'All Accounts Manually Verified Before Listing',
                '30-Day Guarantee on Account Validity',
            ]],
            ['heading' => 'How to Purchase', 'type' => 'list', 'list_class' => 'started-list', 'items' => [
                'Add the account to your cart',
                'Proceed to checkout and complete payment',
                'Receive account credentials instantly via email',
                'Log in and change your password immediately',
                'Enjoy your account — contact support if you need help',
            ]],
            ['heading' => 'Frequently Asked Questions', 'type' => 'faq', 'items' => [
                ['question' => 'Is this account safe to use?',         'answer' => 'Yes. All accounts are verified, in good standing, and have never been banned. We guarantee account validity for 30 days.'],
                ['question' => 'How long does delivery take?',         'answer' => 'Delivery is fully automated — credentials arrive within seconds of payment confirmation.'],
                ['question' => 'Can I change the email after purchase?','answer' => 'Yes, you have full email access. We recommend changing the password and email immediately.'],
                ['question' => 'What if the account is not as described?','answer' => 'Contact support within 24 hours and we will verify the issue and provide a replacement or full refund.'],
            ]],
        ];
    }

    private function inactiveExclusiveDesc(int $count, string $region): array
    {
        return [
            ['heading' => 'Account Description', 'type' => 'paragraph', 'items' => [
                "Inactive exclusive {$region} Valorant account with {$count} skins. This account has been inactive for over 90 days and features rare skins no longer available in the store. Comes with full mail access and complete ownership.",
            ]],
            ['heading' => 'Key Benefits', 'type' => 'list', 'items' => [
                'Full Account Ownership — Email & Password',
                'Full Email Access',
                "{$count} Skins Including Exclusive Titles",
                'All Agents Unlocked',
                'Inactive — Lower Risk of Previous-Owner Conflict',
                '24/7 Customer Support',
            ]],
            ['heading' => "What's Included", 'type' => 'list', 'list_class' => 'offer-list', 'items' => [
                'Full Account Credentials',
                'Original Email Access',
                "{$count} Weapon Skins (Inventory Verified)",
                'High-Tier Exclusive Skins Included',
                'All Agents Unlocked',
                'Account in Good Standing',
            ]],
            ['heading' => 'Our Advantages', 'type' => 'list', 'list_class' => 'account-list', 'items' => [
                'Trusted Seller — 5 000+ Successful Sales',
                'Every Account Manually Reviewed',
                'Skin Inventory Verified Before Listing',
                '30-Day Account Validity Guarantee',
            ]],
            ['heading' => 'How to Purchase', 'type' => 'list', 'list_class' => 'started-list', 'items' => [
                'Add the exclusive account to your cart',
                'Proceed to checkout and complete payment',
                'Receive credentials and email access instantly',
                'Log in and immediately change password + linked email',
                'Contact support within 24 hours if any issue arises',
            ]],
            ['heading' => 'Frequently Asked Questions', 'type' => 'faq', 'items' => [
                ['question' => 'What is an "Inactive Exclusive" account?',  'answer' => "An account not played in over 90 days that features rare and exclusive skins no longer sold in the store."],
                ['question' => 'Are the skins shown guaranteed?',            'answer' => 'Yes. Every skin shown is verified to be in the inventory before listing.'],
                ['question' => 'Is the account safe to use?',               'answer' => 'Yes. The account is in good standing and has no bans or flags.'],
                ['question' => 'How long until I receive the account?',     'answer' => 'Delivery is instant after payment confirmation — full credentials arrive within seconds.'],
            ]],
        ];
    }

    private function fortniteDesc(string $skinRange): array
    {
        return [
            ['heading' => 'Account Description', 'type' => 'paragraph', 'items' => [
                "NFA Fortnite account with {$skinRange} skins. You receive full login credentials. Ideal for players who want great cosmetics at an affordable price.",
            ]],
            ['heading' => 'Key Benefits', 'type' => 'list', 'items' => [
                'Instant Delivery After Payment',
                'Login Credentials Provided',
                '24/7 Customer Support',
                'Account is Safe to Play',
                'Great Cosmetic Value',
            ]],
            ['heading' => "What's Included", 'type' => 'list', 'list_class' => 'offer-list', 'items' => [
                'Fortnite Account Credentials (Email + Password)',
                "{$skinRange} Skins in Locker",
                'Various Cosmetics (Back Blings, Pickaxes, Emotes)',
                'Account Ready to Play on Any Platform',
            ]],
            ['heading' => 'Our Advantages', 'type' => 'list', 'list_class' => 'account-list', 'items' => [
                'Trusted Seller — 5 000+ Successful Sales',
                'Automated Instant Delivery',
                'All Accounts Checked Before Listing',
                'Stable NFA Accounts',
            ]],
            ['heading' => 'How to Purchase', 'type' => 'list', 'list_class' => 'started-list', 'items' => [
                'Add to cart and checkout',
                'Complete your payment securely',
                'Receive account credentials by email instantly',
                'Log in and enjoy the cosmetics',
            ]],
            ['heading' => 'Frequently Asked Questions', 'type' => 'faq', 'items' => [
                ['question' => 'What is an NFA account?',  'answer' => 'NFA = No Full Access. You get login credentials but not the original email. Entry-level accounts ideal for cosmetics.'],
                ['question' => 'Is the account safe?',     'answer' => 'Yes. NFA accounts are completely safe to play on with no bans or flags.'],
                ['question' => 'How fast is delivery?',    'answer' => 'Delivery is instant and automated — credentials arrive within seconds of payment.'],
                ['question' => 'Can I use this on console?','answer' => 'Yes. Fortnite accounts work on PC, PlayStation, Xbox, Nintendo Switch, and mobile.'],
            ]],
        ];
    }

    private function legendsDesc(int $skins, int $champs, string $region): array
    {
        return [
            ['heading' => 'Account Description', 'type' => 'paragraph', 'items' => [
                "League of Legends {$region} account with {$skins} skins, {$champs} champions, and full mail access. A premium account ready for ranked play.",
            ]],
            ['heading' => 'Key Benefits', 'type' => 'list', 'items' => [
                'Instant Delivery After Payment',
                'Full Mail Access Included',
                "{$skins} Skins in Collection",
                "{$champs} Champions Unlocked",
                '24/7 Customer Support',
            ]],
            ['heading' => "What's Included", 'type' => 'list', 'list_class' => 'offer-list', 'items' => [
                'Full Account Credentials',
                'Original Email Access',
                "{$skins} Skins Across Champions",
                "{$champs} Champions Unlocked",
                'Account in Good Standing',
            ]],
            ['heading' => 'Our Advantages', 'type' => 'list', 'list_class' => 'account-list', 'items' => [
                'Trusted Seller with Thousands of Sales',
                'All Accounts Manually Verified',
                '30-Day Account Validity Guarantee',
                'Fast & Automated Delivery',
            ]],
            ['heading' => 'How to Purchase', 'type' => 'list', 'list_class' => 'started-list', 'items' => [
                'Add to cart and proceed to checkout',
                'Complete payment via Stripe or Crypto',
                'Receive account credentials instantly',
                'Log in to the provided email and change the password',
                'Enjoy your new League account',
            ]],
            ['heading' => 'Frequently Asked Questions', 'type' => 'faq', 'items' => [
                ['question' => 'What does "Mail Access" mean?',      'answer' => "Mail Access means you receive full access to the original linked email, giving you complete ownership."],
                ['question' => 'Is the account banned or flagged?',  'answer' => 'No. All accounts are in good standing and have never been banned or reported.'],
                ['question' => 'Can I change the linked email?',     'answer' => 'Yes. With mail access, you can change the email to your own for full security.'],
                ['question' => 'How long does delivery take?',       'answer' => 'Delivery is instant after payment — credentials arrive within seconds.'],
            ]],
        ];
    }

    // ─── Valorant inactive_exclusive — rich layout data ───────────────────────

    private function agentsDetailed(): array
    {
        $roleIcons = ['/img/icons/agent_tab_icon01.svg', '/img/icons/agent_tab_icon02.svg', null];
        $agents    = [];
        for ($i = 1; $i <= 24; $i++) {
            $agents[] = [
                'id'        => "agent_{$i}",
                'image'     => "/img/images/tab_img_{$i}.png",
                'role_icon' => $roleIcons[($i - 1) % 3],
            ];
        }
        return $agents;
    }

    private function profileInfo(string $region, int $skinCount): array
    {
        $data = match ($region) {
            'na'    => ['region' => 'North America',  'stats' => ['48', '31', '9200'], 'ranks' => [
                ['image' => '/img/icons/profile_rank01.png', 'title' => 'Gold 1',   'label' => 'Current rank — V26 ACT II'],
                ['image' => '/img/icons/profile_rank02.png', 'title' => 'Silver 3', 'label' => 'Previous act rank — V25 ACT V'],
                ['image' => '/img/icons/profile_rank03.png', 'title' => 'Gold 2',   'label' => 'Maximum rank — V25 ACT II'],
            ]],
            'eu'    => ['region' => 'Europe',          'stats' => ['52', '28', '7800'], 'ranks' => [
                ['image' => '/img/icons/profile_rank02.png', 'title' => 'Gold 2',   'label' => 'Current rank — V26 ACT II'],
                ['image' => '/img/icons/profile_rank01.png', 'title' => 'Silver 1', 'label' => 'Previous act rank — V25 ACT V'],
                ['image' => '/img/icons/profile_rank03.png', 'title' => 'Gold 3',   'label' => 'Maximum rank — V24 ACT I'],
            ]],
            'apac'  => ['region' => 'Asia Pacific',    'stats' => ['38', '22', '6400'], 'ranks' => [
                ['image' => '/img/icons/profile_rank02.png', 'title' => 'Silver 3', 'label' => 'Current rank — V26 ACT II'],
                ['image' => '/img/icons/profile_rank01.png', 'title' => 'Bronze 2', 'label' => 'Previous act rank — V25 ACT V'],
                ['image' => '/img/icons/profile_rank03.png', 'title' => 'Silver 1', 'label' => 'Maximum rank — V25 ACT I'],
            ]],
            'latam' => ['region' => 'Latin America',   'stats' => ['29', '18', '5200'], 'ranks' => [
                ['image' => '/img/icons/profile_rank01.png', 'title' => 'Bronze 3', 'label' => 'Current rank — V26 ACT II'],
                ['image' => '/img/icons/profile_rank02.png', 'title' => 'Bronze 1', 'label' => 'Previous act rank — V25 ACT V'],
                ['image' => '/img/icons/profile_rank03.png', 'title' => 'Silver 2', 'label' => 'Maximum rank — V24 ACT III'],
            ]],
            default => ['region' => 'Brazil',          'stats' => ['41', '25', '7100'], 'ranks' => [
                ['image' => '/img/icons/profile_rank02.png', 'title' => 'Silver 2', 'label' => 'Current rank — V26 ACT II'],
                ['image' => '/img/icons/profile_rank01.png', 'title' => 'Bronze 3', 'label' => 'Previous act rank — V25 ACT V'],
                ['image' => '/img/icons/profile_rank03.png', 'title' => 'Gold 1',   'label' => 'Maximum rank — V25 ACT III'],
            ]],
        };

        return [
            'region'         => $data['region'],
            'region_icon'    => '/img/icons/server_icon.svg',
            'profile_image'  => '/img/images/profile_img.jpg',
            'profile_stats'  => [
                ['icon' => '/img/icons/profile_icon01.svg', 'value' => $data['stats'][0]],
                ['icon' => '/img/icons/profile_icon02.svg', 'value' => $data['stats'][1]],
                ['icon' => '/img/icons/profile_icon03.svg', 'value' => $data['stats'][2]],
            ],
            'inventory_value' => [
                'label' => 'Inventory value',
                'value' => '~' . number_format($skinCount * 2100) . ' VP',
                'icon'  => '/img/icons/valorant.svg',
            ],
            'ranks'    => $data['ranks'],
            'features' => [
                ['icon' => 'mail',  'title' => 'Mail Access'],
                ['icon' => 'clock', 'title' => 'Last Active 31.03.2026'],
                ['icon' => 'phone', 'title' => 'Phone Number Linked', 'red' => true],
            ],
        ];
    }

    private function skinInventory(int $totalCount): array
    {
        // 16 skin items cycling through rarity + weapon_type
        $rarities    = ['ultra', 'exclusive', 'premium', 'deluxe', 'select'];
        $weaponTypes = ['melee', 'rifles', 'sniper_rifles', 'sidearms', 'smgs', 'shotguns', 'machine_guns'];
        $names       = [
            'Champions 2022 Butterfly Knife', 'RGX 11z Pro Phantom', 'Prime Vandal',
            'Ion Operator', 'Reaver Karambit', 'Glitchpop Dagger',
            'Protocol 781-A Phantom', 'Elderflame Operator', 'Forsaken Vandal',
            'Sentinels of Light Vandal', 'BlastX Phantom', 'Ruination Sword',
            'Origin Operator', 'Gravitational Uranium Neuroblaster', 'Imperium Phantom',
            'Spectrum Phantom',
        ];

        $items = [];
        for ($i = 1; $i <= 16; $i++) {
            $item = [
                'id'           => "sk{$i}",
                'name'         => $names[$i - 1],
                'image'        => "/img/valorant/skin_item_{$i}.png",
                'rarity'       => $rarities[($i - 1) % count($rarities)],
                'weapon_type'  => $weaponTypes[($i - 1) % count($weaponTypes)],
                'levels'       => (($i - 1) % 3) + 3,
                'thumb_modifier' => ($i % 5 === 0) ? 'two' : null,
            ];

            // Color variants on the first 4 skins
            if ($i <= 4) {
                $item['color_variants'] = [
                    ['id' => "cv{$i}a", 'image' => '/img/valorant/skin_color_img01.jpg'],
                    ['id' => "cv{$i}b", 'image' => '/img/valorant/skin_color_img02.jpg'],
                    ['id' => "cv{$i}c", 'image' => '/img/valorant/skin_color_img03.jpg'],
                    ['id' => "cv{$i}d", 'image' => '/img/valorant/skin_color_img04.jpg'],
                ];
            }

            $items[] = $item;
        }

        return [
            'total'     => $totalCount,
            'purchased' => (int) round($totalCount * 0.3),
            'vp'        => $totalCount * 2100,
            'items'     => $items,
        ];
    }

    private function skinFilters(): array
    {
        // Counts match the cycling weapon_type assignment in skinInventory()
        return [
            'rarities'     => [
                ['key' => 'ultra',     'label' => 'Ultra',        'icon' => '/img/icons/sidebar_icon01.svg'],
                ['key' => 'exclusive', 'label' => 'Exclusive',    'icon' => '/img/icons/sidebar_icon02.svg'],
                ['key' => 'premium',   'label' => 'Premium',      'icon' => '/img/icons/sidebar_icon03.svg'],
                ['key' => 'deluxe',    'label' => 'Deluxe',       'icon' => '/img/icons/sidebar_icon04.svg'],
                ['key' => 'select',    'label' => 'Select',       'icon' => '/img/icons/sidebar_icon05.svg'],
            ],
            'weapon_types' => [
                ['key' => 'melee',         'label' => 'Melee',        'count' => 3],
                ['key' => 'rifles',        'label' => 'Rifles',       'count' => 3],
                ['key' => 'sniper_rifles', 'label' => 'Sniper Rifles','count' => 2],
                ['key' => 'sidearms',      'label' => 'Sidearms',     'count' => 2],
                ['key' => 'smgs',          'label' => 'SMGs',         'count' => 2],
                ['key' => 'shotguns',      'label' => 'Shotguns',     'count' => 2],
                ['key' => 'machine_guns',  'label' => 'Machine Guns', 'count' => 2],
            ],
        ];
    }

    private function buddyInventory(): array
    {
        $names = [
            'Ep 5 // 1 Coin', 'Pocket Sized Sheriff', 'Champions 2022 Buddy', 'RGX 11z Pro Buddy',
            'Prime Buddy', 'Ion Crystal Buddy', 'Reaver Buddy', 'Glitchpop Buddy',
            'Protocol Buddy', 'Elderflame Buddy', 'Forsaken Buddy', 'Sentinel Buddy',
            'BlastX Buddy', 'Ruination Buddy', 'Origin Buddy', 'GUN Buddy',
            'Butterfly Buddy', 'Diamond Buddy', 'Trophy Buddy', 'Combat Buddy',
        ];

        $items = [];
        for ($i = 1; $i <= 20; $i++) {
            $items[] = [
                'id'    => "b{$i}",
                'name'  => $names[$i - 1],
                'image' => "/img/valorant/buddies_img_{$i}.png",
            ];
        }

        return ['total' => 47, 'purchased' => 0, 'vp' => 0, 'items' => $items];
    }

    // ─── Fortnite nfa_inactive — fortnite_four layout data ────────────────────

    private function accountLevel(int $level): array
    {
        return [
            'value'       => $level,
            'label'       => 'Account Level',
            'description' => 'The account level is the sum of all seasonal levels you have reached since the beginning of your Fortnite journey. A higher level indicates a more experienced and long-standing account.',
        ];
    }

    private function accountStats(array $v): array
    {
        return [
            ['icon' => 'wins',              'label' => 'Wins',                     'value' => $v['wins']],
            ['icon' => 'matches',           'label' => 'Matches',                  'value' => $v['matches']],
            ['icon' => 'gold_bars',         'label' => 'Gold Bars',                'value' => '0'],
            ['icon' => 'vbucks',            'label' => 'Available V-Bucks',        'value' => '0'],
            ['icon' => 'gifts_sent',        'label' => 'Gifts Sent',               'value' => '0'],
            ['icon' => 'gifts_received',    'label' => 'Gifts Received',           'value' => '0'],
            ['icon' => 'tickets_available', 'label' => 'Available Return Tickets', 'value' => 'No'],
            ['icon' => 'tickets_used',      'label' => 'Used Return Tickets',      'value' => 'No'],
            ['icon' => 'skins',             'label' => 'Skins',                    'value' => $v['skins']],
            ['icon' => 'back_blings',       'label' => 'Back Blings',              'value' => $v['bb']],
            ['icon' => 'pickaxes',          'label' => 'Pickaxes',                 'value' => $v['picks']],
            ['icon' => 'emotes',            'label' => 'Emotes',                   'value' => $v['emotes']],
            ['icon' => 'gliders',           'label' => 'Gliders',                  'value' => $v['gliders']],
            ['icon' => 'exclusives',        'label' => 'Exclusives',               'value' => $v['excl']],
        ];
    }

    private function locker(): array
    {
        $exclusives = [
            ['id' => 'ex01', 'name' => 'Black Knight',       'image' => '/img/fortnite/locker_img_01.jpg', 'rarity' => 'exclusive'],
            ['id' => 'ex02', 'name' => 'Royale Knight',      'image' => '/img/fortnite/locker_img_02.jpg', 'rarity' => 'exclusive'],
            ['id' => 'ex03', 'name' => 'Blue Squire',        'image' => '/img/fortnite/locker_img_03.jpg', 'rarity' => 'exclusive'],
            ['id' => 'ex04', 'name' => 'Sparkle Specialist', 'image' => '/img/fortnite/locker_img_04.jpg', 'rarity' => 'exclusive'],
        ];
        $skins = [
            ['id' => 'sk01', 'name' => 'Gold Midas',    'image' => '/img/fortnite/locker_img_05.jpg'],
            ['id' => 'sk02', 'name' => 'Drift',         'image' => '/img/fortnite/locker_img_06.jpg'],
            ['id' => 'sk03', 'name' => 'Omega',         'image' => '/img/fortnite/locker_img_07.jpg'],
            ['id' => 'sk04', 'name' => 'Ragnarok',      'image' => '/img/fortnite/locker_img_08.jpg'],
            ['id' => 'sk05', 'name' => 'Vendetta',      'image' => '/img/fortnite/locker_img_09.jpg'],
            ['id' => 'sk06', 'name' => 'Fusion',        'image' => '/img/fortnite/locker_img_10.jpg'],
            ['id' => 'sk07', 'name' => 'Lynx',          'image' => '/img/fortnite/locker_img_11.jpg'],
            ['id' => 'sk08', 'name' => 'Orin',          'image' => '/img/fortnite/locker_img_12.jpg'],
        ];
        $backBlings = [
            ['id' => 'bb01', 'name' => 'Love Wings',    'image' => '/img/fortnite/locker_img_13.jpg'],
            ['id' => 'bb02', 'name' => 'Shield Maiden', 'image' => '/img/fortnite/locker_img_14.jpg'],
            ['id' => 'bb03', 'name' => 'Hornet Wings',  'image' => '/img/fortnite/locker_img_15.jpg'],
        ];
        $pickaxes = [
            ['id' => 'pk01', 'name' => 'Candy Axe',     'image' => '/img/fortnite/locker_img_16.jpg'],
            ['id' => 'pk02', 'name' => 'AC/DC',         'image' => '/img/fortnite/locker_img_17.jpg'],
            ['id' => 'pk03', 'name' => 'Minty Pickaxe', 'image' => '/img/fortnite/locker_img_18.jpg'],
        ];
        $gliders = [
            ['id' => 'gl01', 'name' => 'Royale Shield', 'image' => '/img/fortnite/locker_img_19.jpg'],
            ['id' => 'gl02', 'name' => 'Laser Chomp',   'image' => '/img/fortnite/locker_img_20.jpg'],
        ];
        $emotes = [
            ['id' => 'em01', 'name' => 'Floss',          'image' => '/img/fortnite/locker_img_21.jpg'],
            ['id' => 'em02', 'name' => 'Orange Justice', 'image' => '/img/fortnite/locker_img_22.jpg'],
            ['id' => 'em03', 'name' => 'Take the L',     'image' => '/img/fortnite/locker_img_23.jpg'],
            ['id' => 'em04', 'name' => 'Electro Swing',  'image' => '/img/fortnite/locker_img_24.jpg'],
        ];

        $allGroups = [
            ['title' => 'Exclusives', 'count' => count($exclusives), 'purchased' => 0, 'vbucks' => 0,    'items' => $exclusives],
            ['title' => 'Skins',      'count' => count($skins),      'purchased' => 2, 'vbucks' => 2000, 'items' => $skins],
        ];

        return [
            'tabs' => [
                ['key' => 'all',         'label' => 'All',         'groups' => $allGroups],
                ['key' => 'exclusives',  'label' => 'Exclusive',   'groups' => [['title' => 'Exclusives', 'count' => count($exclusives), 'purchased' => 0, 'vbucks' => 0, 'items' => $exclusives]]],
                ['key' => 'skins',       'label' => 'Skins',       'groups' => [['title' => 'Skins',      'count' => count($skins),      'purchased' => 2, 'vbucks' => 2000, 'items' => $skins]]],
                ['key' => 'back_blings', 'label' => 'Back Blings', 'groups' => [['title' => 'Back Blings','count' => count($backBlings), 'purchased' => 0, 'vbucks' => 0, 'items' => $backBlings]]],
                ['key' => 'pickaxes',    'label' => 'Pickaxes',    'groups' => [['title' => 'Pickaxes',   'count' => count($pickaxes),   'purchased' => 1, 'vbucks' => 500, 'items' => $pickaxes]]],
                ['key' => 'gliders',     'label' => 'Gliders',     'groups' => [['title' => 'Gliders',    'count' => count($gliders),    'purchased' => 0, 'vbucks' => 0, 'items' => $gliders]]],
                ['key' => 'emotes',      'label' => 'Emotes',      'groups' => [['title' => 'Emotes',     'count' => count($emotes),     'purchased' => 0, 'vbucks' => 0, 'items' => $emotes]]],
            ],
        ];
    }

    private function seasons(int $currentSeason): array
    {
        $currentStats = [
            ['icon' => 'rank',         'label' => 'Zero Build Rank:',    'value' => 'Bronze I (0%)'],
            ['icon' => 'rank',         'label' => 'Battle Royale Rank:', 'value' => 'Bronze I (0%)'],
            ['icon' => 'level',        'label' => 'Level:',              'value' => '1'],
            ['icon' => 'season_wins',  'label' => 'Season Wins:',        'value' => 'No'],
            ['icon' => 'bp_level',     'label' => 'BP Level:',           'value' => '1'],
            ['icon' => 'bp_purchased', 'label' => 'BP Purchased:',       'value' => 'No'],
            ['icon' => 'last_match',   'label' => 'Last Match:',         'value' => '09.05.2024'],
        ];

        $history = [];
        for ($s = 1; $s <= $currentSeason; $s++) {
            $history[] = [
                'season'       => $s,
                'level'        => rand(1, 100),
                'season_wins'  => $s > 3 ? (string) rand(0, 15) : 'No',
                'bp_level'     => rand(1, 100),
                'bp_purchased' => $s > 2 ? 'Yes' : 'No',
            ];
        }

        return [
            'current'            => ['number' => $currentSeason, 'stats' => $currentStats],
            'history'            => $history,
            'chapter_title'      => 'Chapter 1',
            'history_background' => '/img/fortnite/seasons-img.jpg',
        ];
    }
}
