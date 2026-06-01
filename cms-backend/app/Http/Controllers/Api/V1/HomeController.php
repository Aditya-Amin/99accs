<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FooterWidget;
use App\Models\HomeSection;
use App\Models\Testimonial;

class HomeController extends Controller
{
    public function index()
    {
        $testimonialSection = HomeSection::getSection('testimonials');

        // ── Banner ──────────────────────────────────────────────────────────
        $rawBanner = HomeSection::getSection('banner');
        $banner    = $this->withDefaults($rawBanner, $this->bannerDefaults());
        $banner['background_image'] = $this->resolveImg($banner['background_image'] ?? null);
        // Legacy heading array → HTML string
        if (is_array($banner['heading'] ?? null)) {
            $h = $banner['heading'];
            $banner['heading'] = htmlspecialchars($h['prefix'] ?? '', ENT_QUOTES, 'UTF-8')
                . '<span>' . htmlspecialchars($h['highlight'] ?? '', ENT_QUOTES, 'UTF-8') . '</span>'
                . htmlspecialchars($h['suffix'] ?? '', ENT_QUOTES, 'UTF-8');
        }
        // Repeater arrays: use stored data exclusively (re-indexed) so UUID keys from Filament
        // don't produce mixed-key arrays that json_encode encodes as objects instead of arrays.
        $banner['features'] = $this->pluckRepeater($rawBanner, 'features', $banner['features'] ?? []);
        $banner['categories'] = array_map(
            fn ($c) => array_merge($c, ['image' => $this->resolveImg($c['image'] ?? null)]),
            $this->pluckRepeater($rawBanner, 'categories', $banner['categories'] ?? [])
        );

        // ── About ────────────────────────────────────────────────────────────
        $rawAbout = HomeSection::getSection('about');
        $about    = $this->withDefaults($rawAbout, $this->aboutDefaults());
        $about['background_image'] = $this->resolveImg($about['background_image'] ?? null);
        $about['image']            = $this->resolveImg($about['image'] ?? null);
        // Simple repeater: each item may be a string or a single-key array {'': text}
        $about['paragraphs'] = isset($rawAbout['paragraphs'])
            ? array_values(array_filter(
                array_values((array) $rawAbout['paragraphs']),
                fn ($p) => is_string($p) && $p !== ''
              ))
            : array_values($about['paragraphs'] ?? []);

        // ── Work ─────────────────────────────────────────────────────────────
        $rawWork = HomeSection::getSection('work');
        $work    = $this->withDefaults($rawWork, $this->workDefaults());
        $work['background_image'] = $this->resolveImg($work['background_image'] ?? null);
        $work['steps']  = $this->pluckRepeater($rawWork, 'steps', $work['steps'] ?? []);
        $work['images'] = array_values(array_filter(array_map(
            fn ($img) => $this->resolveImg($img),
            isset($rawWork['images']) ? (array) $rawWork['images'] : ($work['images'] ?? [])
        )));

        // ── Features ─────────────────────────────────────────────────────────
        $rawFeatures = HomeSection::getSection('features');
        $features    = $this->withDefaults($rawFeatures, $this->featuresDefaults());
        $features['background_image'] = $this->resolveImg($features['background_image'] ?? null);
        $rawFeatureItems = array_values($this->pluckRepeater($rawFeatures, 'items', $features['items'] ?? []));
        $features['items'] = array_map(
            fn ($item, $index) => array_merge(['id' => $index + 1], $item, ['icon' => $this->resolveImg($item['icon'] ?? null)]),
            $rawFeatureItems,
            array_keys($rawFeatureItems)
        );

        // ── CTA ───────────────────────────────────────────────────────────────
        $rawCta = HomeSection::getSection('cta');
        $cta    = $this->withDefaults($rawCta, $this->ctaDefaults());
        $cta['background_image'] = $this->resolveImg($cta['background_image'] ?? null);
        $rawCtaButtons = array_values($this->pluckRepeater($rawCta, 'buttons', $cta['buttons'] ?? []));
        $cta['buttons'] = array_map(
            fn ($btn, $index) => array_merge(['id' => $index + 1], $btn),
            $rawCtaButtons,
            array_keys($rawCtaButtons)
        );

        return response()->json([
            'data' => [
                'banner'       => $banner,
                'about'        => $about,
                'work'         => $work,
                'features'     => $features,
                'testimonials' => [
                    'background_image' => $this->resolveImg($testimonialSection['background_image'] ?? '/img/bg/testimonial_bg.png'),
                    'title'            => $testimonialSection['title'] ?? 'What our customers are saying',
                    'items'            => Testimonial::published()->ordered()->get()->map(fn ($t) => [
                        'id'     => $t->id,
                        'title'  => $t->title,
                        'text'   => $t->text,
                        'author' => $t->author,
                        'rating' => $t->rating,
                    ])->toArray(),
                ],
                'cta'          => $cta,
            ],
        ]);
    }

    public function footer()
    {
        $rawSettings = HomeSection::getSection('footer_settings');
        $settings    = $this->withDefaults($rawSettings, $this->footerSettingsDefaults());

        $settings['logo']          = $this->resolveImg($settings['logo'] ?? null);
        $settings['payment_image'] = $this->resolveImg($settings['payment_image'] ?? null);
        $settings['social_links']  = $this->pluckRepeater($rawSettings, 'social_links', $settings['social_links'] ?? []);
        $settings['quick_links']   = $this->pluckRepeater($rawSettings, 'quick_links',  $settings['quick_links']  ?? []);

        $widgets = FooterWidget::active()->ordered()->get()->map(function ($w) {
            $config = $w->config ?? [];
            if (isset($config['links']) && is_array($config['links'])) {
                $config['links'] = array_values($config['links']);
            }
            if (array_key_exists('icon_url', $config)) {
                $config['icon_url'] = $this->resolveImg($config['icon_url']);
            }
            return [
                'id'        => $w->id,
                'type'      => $w->type,
                'col_class' => $w->col_class,
                'config'    => $config,
            ];
        })->values()->toArray();

        return response()->json([
            'data' => [
                'settings' => $settings,
                'widgets'  => $widgets,
            ],
        ]);
    }

    /**
     * Return the stored repeater array for $field (re-indexed, UUID keys stripped),
     * or fall back to $default when the admin has not saved that field yet.
     * array_replace_recursive mixes UUID-keyed stored items with integer-keyed defaults,
     * producing mixed-key arrays that json_encode serialises as objects not arrays.
     * Bypassing the merge for repeater fields avoids that entirely.
     */
    private function pluckRepeater(array $raw, string $field, array $default): array
    {
        return isset($raw[$field]) ? array_values((array) $raw[$field]) : array_values($default);
    }

    private function withDefaults(array $stored, array $defaults): array
    {
        return array_replace_recursive($defaults, $stored);
    }

    private function resolveImg(mixed $path): ?string
    {
        if (! $path) return null;
        if (is_int($path) || (is_string($path) && ctype_digit($path))) {
            $media = \App\Models\CuratorMedia::find((int) $path);
            return $media ? $media->url : null;
        }
        if (str_starts_with($path, '/') || str_starts_with($path, 'http')) return $path;
        return asset('storage/' . ltrim($path, '/'));
    }

    // ─── Default payloads (used when admin hasn't saved the section yet) ──────

    private function bannerDefaults(): array
    {
        return [
            'background_image' => '/img/bg/hero_bg.jpg',
            'subtitle'         => ['icon' => 'shield_check', 'text' => 'Your purchases on 99Accs are protected by us.'],
            'heading'          => 'Your Trusted <span>Marketplace</span> for Game Accounts',
            'description'      => 'Discover carefully selected high rank accounts and rare skins.',
            'features'         => [
                ['id' => 1, 'icon' => 'trophy',   'text' => 'High-Quality Accounts'],
                ['id' => 2, 'icon' => 'delivery', 'text' => 'Instant Delivery After Payment'],
                ['id' => 3, 'icon' => 'warranty', 'text' => 'Free Warranty and Support'],
            ],
            'categories' => [
                ['id' => 1, 'href' => '/shop/valorant', 'image' => '/img/images/hero_cat01.jpg', 'alt' => 'Valorant'],
                ['id' => 2, 'href' => '/shop/fortnite', 'image' => '/img/images/hero_cat02.jpg', 'alt' => 'Fortnite'],
                ['id' => 3, 'href' => '/shop/legends',  'image' => '/img/images/hero_cat03.jpg', 'alt' => 'League of Legends'],
            ],
        ];
    }

    private function aboutDefaults(): array
    {
        return [
            'background_image' => '/img/bg/about_bg.png',
            'image'            => '/img/images/about_img.png',
            'title'            => 'We help you play your way and enjoy your games',
            'paragraphs'       => [
                'Since 2020, 99access has connected dedicated gamers with the accounts and skins they need.',
                'We understand gaming culture and are passionate about making rare items accessible.',
                'Your in-game progress shapes you as a player — we help you get there faster.',
            ],
            'stats' => ['happy_customers' => 48345, 'accounts_sold' => 67234],
        ];
    }

    private function workDefaults(): array
    {
        return [
            'background_image' => '/img/bg/work_bg.png',
            'title'            => 'How does it work?',
            'steps'            => [
                ['id' => 1, 'num' => '01', 'title' => 'Select an Account',        'text' => 'Browse our curated catalog and pick the account or item you want.'],
                ['id' => 2, 'num' => '02', 'title' => 'Enter Your Email Address', 'text' => 'We send your purchase details and credentials securely to your email.'],
                ['id' => 3, 'num' => '03', 'title' => 'Complete Payment',         'text' => 'Pay safely via card or crypto — all transactions are encrypted.'],
                ['id' => 4, 'num' => '04', 'title' => 'Instant Delivery',         'text' => 'Receive your account details instantly after payment confirmation.'],
            ],
            'images' => ['/img/images/work_img_01.png', '/img/images/work_img_02.png', '/img/images/work_img_03.png', '/img/images/work_img_04.png'],
        ];
    }

    private function featuresDefaults(): array
    {
        return [
            'background_image' => '/img/bg/features_bg.png',
            'heading'          => ['prefix' => 'More than ', 'user_count' => 48345, 'suffix' => ' gamers rely on 99accs for their online gaming needs'],
            'items'            => [
                ['id' => 1, 'title' => 'Trusted Source',  'icon' => '/img/icons/features_icon01.png', 'text' => 'Every account is verified and tested before listing.'],
                ['id' => 2, 'title' => 'Global Delivery', 'icon' => '/img/icons/features_icon02.png', 'text' => 'We deliver to players worldwide across all regions.'],
                ['id' => 3, 'title' => '24/7 Support',    'icon' => '/img/icons/features_icon03.png', 'text' => 'Our support team is available around the clock.'],
                ['id' => 4, 'title' => 'Secure Warranty', 'icon' => '/img/icons/features_icon04.png', 'text' => 'All purchases come with a full warranty guarantee.'],
            ],
        ];
    }

    private function ctaDefaults(): array
    {
        return [
            'background_image' => '/img/bg/cta_bg.jpg',
            'title_lines'      => ['JOIN THE', 'COMMUNITY'],
            'buttons'          => [
                ['id' => 1, 'platform' => 'telegram', 'label' => 'Join Telegram', 'url' => '#'],
                ['id' => 2, 'platform' => 'discord',  'label' => 'Join Discord',  'url' => '#'],
            ],
        ];
    }

    private function footerSettingsDefaults(): array
    {
        return [
            'logo'               => '/img/logo/logo.svg',
            'logo_href'          => '/',
            'social_links'       => [
                ['platform' => 'discord',   'url' => '#'],
                ['platform' => 'telegram',  'url' => '#'],
                ['platform' => 'facebook',  'url' => '#'],
                ['platform' => 'instagram', 'url' => '#'],
            ],
            'quick_links'        => [
                ['icon' => 'submit_ticket',    'label' => 'Submit Ticket',     'href' => '/support/contact'],
                ['icon' => 'account_email',    'label' => 'Account Email',     'href' => '/account'],
                ['icon' => 'support_articles', 'label' => 'Support Articles',  'href' => '/support/articles'],
                ['icon' => 'faq',              'label' => 'FAQ',               'href' => '#'],
                ['icon' => 'terms',            'label' => 'Terms of Service',  'href' => '#'],
                ['icon' => 'privacy',          'label' => 'Privacy Policy',    'href' => '#'],
                ['icon' => 'cookie',           'label' => 'Cookie Policy',     'href' => '#'],
                ['icon' => 'cart',             'label' => 'Cart',              'href' => '/cart'],
                ['icon' => 'blog',             'label' => 'Blog',              'href' => '#'],
            ],
            'payment_image'      => '/img/images/cart.png',
            'copyright'          => 'Copyright © 2021-{year} All Rights Reserved By',
            'copyright_site_name'=> '99accs.com',
            'copyright_href'     => '/',
        ];
    }
}
