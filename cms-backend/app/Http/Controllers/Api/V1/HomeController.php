<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HomeSection;
use App\Models\Testimonial;

class HomeController extends Controller
{
    public function index()
    {
        $testimonialSection = HomeSection::getSection('testimonials');

        return response()->json([
            'data' => [
                'banner'       => $this->withDefaults(HomeSection::getSection('banner'), $this->bannerDefaults()),
                'about'        => $this->withDefaults(HomeSection::getSection('about'), $this->aboutDefaults()),
                'work'         => $this->withDefaults(HomeSection::getSection('work'), $this->workDefaults()),
                'features'     => $this->withDefaults(HomeSection::getSection('features'), $this->featuresDefaults()),
                'testimonials' => [
                    'background_image' => $testimonialSection['background_image'] ?? '/img/bg/testimonial_bg.png',
                    'title'            => $testimonialSection['title'] ?? 'What our customers are saying',
                    'items'            => Testimonial::published()->ordered()->get()->map(fn ($t) => [
                        'id'     => $t->id,
                        'title'  => $t->title,
                        'text'   => $t->text,
                        'author' => $t->author,
                        'rating' => $t->rating,
                    ])->toArray(),
                ],
                'cta'          => $this->withDefaults(HomeSection::getSection('cta'), $this->ctaDefaults()),
            ],
        ]);
    }

    private function withDefaults(array $stored, array $defaults): array
    {
        return array_replace_recursive($defaults, $stored);
    }

    // ─── Default payloads (used when admin hasn't saved the section yet) ──────

    private function bannerDefaults(): array
    {
        return [
            'background_image' => '/img/bg/hero_bg.jpg',
            'subtitle'         => ['icon' => 'shield_check', 'text' => 'Your purchases on 99Accs are protected by us.'],
            'heading'          => ['prefix' => 'Your Trusted ', 'highlight' => 'Marketplace', 'suffix' => ' for Game Accounts'],
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
}
