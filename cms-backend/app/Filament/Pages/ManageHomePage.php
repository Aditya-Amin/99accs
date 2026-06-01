<?php

namespace App\Filament\Pages;

use App\Models\HomeSection;
use App\Models\Page as PageModel;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;

class ManageHomePage extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $navigationGroup = 'Content';
    protected static ?int    $navigationSort  = 0;
    protected static ?string $title           = 'Home Page';
    protected static string  $view            = 'filament.pages.manage-home-page';

    public ?array $data = [];

    public function mount(): void
    {
        $banner = HomeSection::getSection('banner');

        // Normalize legacy heading array {prefix, highlight, suffix} → HTML string
        if (isset($banner['heading']) && is_array($banner['heading'])) {
            $p = htmlspecialchars($banner['heading']['prefix'] ?? '', ENT_QUOTES, 'UTF-8');
            $h = htmlspecialchars($banner['heading']['highlight'] ?? '', ENT_QUOTES, 'UTF-8');
            $s = htmlspecialchars($banner['heading']['suffix'] ?? '', ENT_QUOTES, 'UTF-8');
            $banner['heading'] = "{$p}<span>{$h}</span>{$s}";
        }

        $about = HomeSection::getSection('about');
        if (isset($about['paragraphs']) && is_array($about['paragraphs'])) {
            // Wrap plain strings into {text: value} objects so the named Repeater field binds correctly.
            // Also handles legacy {'': value} format from the old simple() Repeater.
            $about['paragraphs'] = array_values(array_filter(
                array_map(function ($p) {
                    $str = is_array($p) ? (string) (reset($p)) : (string) $p;
                    return trim($str) !== '' ? ['text' => $str] : null;
                }, $about['paragraphs'])
            ));
        }

        $features = HomeSection::getSection('features');
        // Normalize legacy heading array {prefix, user_count, suffix} → HTML string
        if (isset($features['heading']) && is_array($features['heading'])) {
            $p = htmlspecialchars($features['heading']['prefix']    ?? '', ENT_QUOTES, 'UTF-8');
            $n = htmlspecialchars((string)($features['heading']['user_count'] ?? ''), ENT_QUOTES, 'UTF-8');
            $s = htmlspecialchars($features['heading']['suffix']    ?? '', ENT_QUOTES, 'UTF-8');
            $features['heading'] = "{$p}<span>{$n}</span>{$s}";
        }

        $this->form->fill([
            'banner'   => $banner,
            'about'    => $about,
            'work'     => HomeSection::getSection('work'),
            'features' => $features,
            'cta'      => HomeSection::getSection('cta'),
            'footer'   => HomeSection::getSection('footer_settings'),
        ]);
    }

    private static function pageOptions(): array
    {
        return PageModel::published()
            ->orderBy('title')
            ->get(['title', 'slug'])
            ->mapWithKeys(fn ($p) => ["/{$p->slug}" => $p->title])
            ->prepend('Home', '/')
            ->toArray();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('home_tabs')
                    ->tabs([

                        // ── Banner ──────────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Banner / Hero')
                            ->icon('heroicon-m-photo')
                            ->schema([

                                Forms\Components\Section::make('Background Image')
                                    ->description('Full-width image displayed behind the hero content.')
                                    ->icon('heroicon-m-photo')
                                    ->schema([
                                        \Awcodes\Curator\Components\Forms\CuratorPicker::make('banner.background_image')
                                            ->label('Background Image'),
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Hero Text')
                                    ->description('Subtitle badge, main headline, and supporting description.')
                                    ->icon('heroicon-m-cursor-arrow-rays')
                                    ->schema([
                                        Forms\Components\Select::make('banner.subtitle.icon')
                                            ->label('Subtitle Icon')
                                            ->options(['shield_check' => 'Shield Check', 'trophy' => 'Trophy', 'delivery' => 'Delivery', 'warranty' => 'Warranty'])
                                            ->native(false),

                                        Forms\Components\TextInput::make('banner.subtitle.text')
                                            ->label('Subtitle Text')
                                            ->placeholder('Your purchases are protected by us.'),

                                        Forms\Components\Textarea::make('banner.heading')
                                            ->label('Heading HTML')
                                            ->helperText('Wrap the accent word in <span>…</span>. Allowed tags: span, strong, em, br, mark.')
                                            ->placeholder('Your Trusted <span>Marketplace</span> for Game Accounts')
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('banner.description')
                                            ->label('Description')
                                            ->placeholder('Discover carefully selected high rank accounts and rare skins.')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Feature Chips')
                                    ->description('Small icon badges displayed beneath the headline.')
                                    ->icon('heroicon-m-sparkles')
                                    ->schema([
                                        Forms\Components\Repeater::make('banner.features')
                                            ->hiddenLabel()
                                            ->schema([
                                                Forms\Components\Select::make('icon')
                                                    ->options(['trophy' => 'Trophy', 'delivery' => 'Delivery', 'warranty' => 'Warranty', 'shield_check' => 'Shield'])
                                                    ->native(false)->required(),
                                                Forms\Components\TextInput::make('text')
                                                    ->required()
                                                    ->placeholder('e.g. High-Quality Accounts'),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->reorderable()
                                            ->addActionLabel('Add Chip')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Game Category Cards')
                                    ->description('Game thumbnail links shown as image cards in the hero.')
                                    ->icon('heroicon-m-squares-2x2')
                                    ->schema([
                                        Forms\Components\Repeater::make('banner.categories')
                                            ->hiddenLabel()
                                            ->schema([
                                                Forms\Components\TextInput::make('alt')->required()->label('Game Name')->placeholder('e.g. Valorant'),
                                                Forms\Components\TextInput::make('href')->required()->label('Link URL')->placeholder('/shop/valorant'),
                                                \Awcodes\Curator\Components\Forms\CuratorPicker::make('image')
                                                    ->label('Category Image'),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->reorderable()
                                            ->addActionLabel('Add Game Category')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                            ]),

                        // ── About ────────────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('About')
                            ->icon('heroicon-m-information-circle')
                            ->schema([

                                Forms\Components\Section::make('Section Images')
                                    ->description('Background and side image shown in the About section.')
                                    ->icon('heroicon-m-photo')
                                    ->schema([
                                        \Awcodes\Curator\Components\Forms\CuratorPicker::make('about.background_image')
                                            ->label('Background Image'),
                                        \Awcodes\Curator\Components\Forms\CuratorPicker::make('about.image')
                                            ->label('Section Image'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Content')
                                    ->description('Section title and body paragraphs.')
                                    ->icon('heroicon-m-document-text')
                                    ->schema([
                                        Forms\Components\TextInput::make('about.title')
                                            ->label('Section Title')
                                            ->placeholder('We help you play your way and enjoy your games')
                                            ->columnSpanFull(),

                                        Forms\Components\Repeater::make('about.paragraphs')
                                            ->label('Paragraphs')
                                            ->schema([
                                                Forms\Components\Textarea::make('text')
                                                    ->rows(2)
                                                    ->hiddenLabel()
                                                    ->placeholder('Write a paragraph about your company…')
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(0)
                                            ->reorderable()
                                            ->addActionLabel('Add Paragraph')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Statistics')
                                    ->description('Animated counter figures shown below the body text.')
                                    ->icon('heroicon-m-chart-bar')
                                    ->schema([
                                        Forms\Components\TextInput::make('about.stats.happy_customers')
                                            ->label('Happy Customers')
                                            ->numeric()
                                            ->placeholder('48345'),

                                        Forms\Components\TextInput::make('about.stats.accounts_sold')
                                            ->label('Accounts Sold')
                                            ->numeric()
                                            ->placeholder('67234'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                            ]),

                        // ── How it Works ─────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('How it Works')
                            ->icon('heroicon-m-list-bullet')
                            ->schema([

                                Forms\Components\Section::make('Background Image')
                                    ->description('Background image displayed behind the steps section.')
                                    ->icon('heroicon-m-photo')
                                    ->schema([
                                        \Awcodes\Curator\Components\Forms\CuratorPicker::make('work.background_image')
                                            ->label('Background Image'),
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Steps')
                                    ->description('Numbered steps guiding customers through the purchase flow.')
                                    ->icon('heroicon-m-list-bullet')
                                    ->schema([
                                        Forms\Components\TextInput::make('work.title')
                                            ->label('Section Title')
                                            ->placeholder('How does it work?')
                                            ->columnSpanFull(),

                                        Forms\Components\Repeater::make('work.steps')
                                            ->hiddenLabel()
                                            ->schema([
                                                Forms\Components\TextInput::make('num')->label('Step No.')->placeholder('01')->maxLength(4),
                                                Forms\Components\TextInput::make('title')->required()->placeholder('Select an Account'),
                                                Forms\Components\Textarea::make('text')->rows(2)->columnSpanFull()->placeholder('Describe this step…'),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->reorderable()
                                            ->addActionLabel('Add Step')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Step Gallery')
                                    ->description('Images shown alongside the steps — one per step recommended.')
                                    ->icon('heroicon-m-squares-plus')
                                    ->schema([
                                        \Awcodes\Curator\Components\Forms\CuratorPicker::make('work.images')
                                            ->hiddenLabel()
                                            ->multiple()
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                            ]),

                        // ── Features ─────────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Features')
                            ->icon('heroicon-m-star')
                            ->schema([

                                Forms\Components\Section::make('Background Image')
                                    ->description('Background image for the features grid section.')
                                    ->icon('heroicon-m-photo')
                                    ->schema([
                                        \Awcodes\Curator\Components\Forms\CuratorPicker::make('features.background_image')
                                            ->label('Background Image'),
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Section Heading')
                                    ->description('Main headline for the features grid. Wrap the accent number or word in <span>…</span>.')
                                    ->icon('heroicon-m-pencil')
                                    ->schema([
                                        Forms\Components\Textarea::make('features.heading')
                                            ->label('Heading HTML')
                                            ->helperText('Allowed tags: span, strong, em, br, mark. Example: More than <span>48 000</span> gamers rely on 99accs.')
                                            ->placeholder('More than <span>48345</span> gamers rely on 99accs for their online gaming needs')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Feature Cards')
                                    ->description('Icon cards highlighting your platform\'s key selling points.')
                                    ->icon('heroicon-m-star')
                                    ->schema([
                                        Forms\Components\Repeater::make('features.items')
                                            ->hiddenLabel()
                                            ->schema([
                                                Forms\Components\TextInput::make('title')->required()->placeholder('Trusted Source'),
                                                \Awcodes\Curator\Components\Forms\CuratorPicker::make('icon')
                                                    ->label('Icon'),
                                                Forms\Components\Textarea::make('text')->rows(2)->columnSpanFull()->placeholder('Describe this feature…'),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->reorderable()
                                            ->addActionLabel('Add Feature Card')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                            ]),

                        // ── Footer Settings ──────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Footer Settings')
                            ->icon('heroicon-m-building-storefront')
                            ->schema([

                                Forms\Components\Section::make('Logo')
                                    ->description('Site logo displayed in the footer top-left area.')
                                    ->icon('heroicon-m-photo')
                                    ->schema([
                                        \Awcodes\Curator\Components\Forms\CuratorPicker::make('footer.logo')
                                            ->label('Logo Image'),
                                        Forms\Components\Select::make('footer.logo_href')
                                            ->label('Logo Link URL')
                                            ->options(fn () => static::pageOptions())
                                            ->searchable()
                                            ->native(false)
                                            ->placeholder('Select a page…'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Social Links')
                                    ->description('Social platform icon links shown next to the logo.')
                                    ->icon('heroicon-m-share')
                                    ->schema([
                                        Forms\Components\Repeater::make('footer.social_links')
                                            ->hiddenLabel()
                                            ->schema([
                                                Forms\Components\Select::make('platform')
                                                    ->options(['discord' => 'Discord', 'telegram' => 'Telegram', 'facebook' => 'Facebook', 'instagram' => 'Instagram'])
                                                    ->required()
                                                    ->native(false),
                                                Forms\Components\TextInput::make('url')
                                                    ->label('URL')
                                                    ->required()
                                                    ->placeholder('https://discord.gg/…'),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->reorderable()
                                            ->addActionLabel('Add Social Link')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Quick Links Bar')
                                    ->description('Icon links displayed in the bar between the widget row and the footer bottom. Manage Footer Widgets separately from the sidebar.')
                                    ->icon('heroicon-m-bars-3')
                                    ->schema([
                                        Forms\Components\Repeater::make('footer.quick_links')
                                            ->hiddenLabel()
                                            ->schema([
                                                Forms\Components\Select::make('icon')
                                                    ->options([
                                                        'submit_ticket'    => 'Submit Ticket',
                                                        'account_email'    => 'Account Email',
                                                        'support_articles' => 'Support Articles',
                                                        'faq'              => 'FAQ',
                                                        'terms'            => 'Terms of Service',
                                                        'privacy'          => 'Privacy Policy',
                                                        'cookie'           => 'Cookie Policy',
                                                        'cart'             => 'Cart',
                                                        'blog'             => 'Blog',
                                                    ])
                                                    ->required()
                                                    ->native(false),
                                                Forms\Components\TextInput::make('label')
                                                    ->required()
                                                    ->placeholder('Submit Ticket'),
                                                Forms\Components\Select::make('href')
                                                    ->label('Page / URL')
                                                    ->options(fn () => static::pageOptions())
                                                    ->searchable()
                                                    ->native(false),
                                            ])
                                            ->columns(3)
                                            ->defaultItems(0)
                                            ->reorderable()
                                            ->addActionLabel('Add Quick Link')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Footer Bottom')
                                    ->description('Payment icons image and copyright text.')
                                    ->icon('heroicon-m-credit-card')
                                    ->schema([
                                        \Awcodes\Curator\Components\Forms\CuratorPicker::make('footer.payment_image')
                                            ->label('Payment Methods Image'),
                                        Forms\Components\TextInput::make('footer.copyright')
                                            ->label('Copyright Text')
                                            ->placeholder('Copyright © 2021-{year} All Rights Reserved By')
                                            ->helperText('Use {year} as a placeholder for the current year.'),
                                        Forms\Components\TextInput::make('footer.copyright_site_name')
                                            ->label('Linked Site Name')
                                            ->placeholder('99accs.com'),
                                        Forms\Components\TextInput::make('footer.copyright_href')
                                            ->label('Copyright Link URL')
                                            ->placeholder('/'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                            ]),

                        // ── CTA ──────────────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('CTA')
                            ->icon('heroicon-m-megaphone')
                            ->schema([

                                Forms\Components\Section::make('Background Image')
                                    ->description('Background image for the community call-to-action section.')
                                    ->icon('heroicon-m-photo')
                                    ->schema([
                                        \Awcodes\Curator\Components\Forms\CuratorPicker::make('cta.background_image')
                                            ->label('Background Image'),
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Call to Action')
                                    ->description('Headline text lines and social platform join buttons.')
                                    ->icon('heroicon-m-megaphone')
                                    ->schema([
                                        Forms\Components\TagsInput::make('cta.title_lines')
                                            ->label('Title Lines')
                                            ->helperText('Press Enter after each line. Displayed as a stacked headline.')
                                            ->placeholder('Add a line…')
                                            ->columnSpanFull(),

                                        Forms\Components\Repeater::make('cta.buttons')
                                            ->label('Social Buttons')
                                            ->schema([
                                                Forms\Components\Select::make('platform')
                                                    ->options(['telegram' => 'Telegram', 'discord' => 'Discord', 'twitter' => 'Twitter', 'youtube' => 'YouTube'])
                                                    ->native(false)->required(),
                                                Forms\Components\TextInput::make('label')->required()->placeholder('Join Discord'),
                                                Forms\Components\TextInput::make('url')->required()->placeholder('https://discord.gg/…'),
                                            ])
                                            ->columns(3)
                                            ->defaultItems(0)
                                            ->reorderable()
                                            ->addActionLabel('Add Button')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                            ]),

                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Home Page')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            if (isset($data['banner']['heading']) && is_string($data['banner']['heading'])) {
                $data['banner']['heading'] = strip_tags($data['banner']['heading'], '<span><strong><em><br><mark>');
            }

            if (isset($data['features']['heading']) && is_string($data['features']['heading'])) {
                $data['features']['heading'] = strip_tags($data['features']['heading'], '<span><strong><em><br><mark>');
            }

            // Unwrap [{text: 'p1'}, {text: 'p2'}] → ['p1', 'p2'] before storing.
            if (isset($data['about']['paragraphs']) && is_array($data['about']['paragraphs'])) {
                $data['about']['paragraphs'] = array_values(array_filter(
                    array_map(fn ($item) => trim(is_array($item) ? ($item['text'] ?? '') : (string) $item),
                        array_values($data['about']['paragraphs'])
                    ),
                    fn ($p) => $p !== ''
                ));
            }

            // Strip Filament's internal UUID keys from every Repeater field so the DB
            // always stores clean sequential arrays (json_encode → [] not {}).
            foreach ([
                ['banner',   'features'],
                ['banner',   'categories'],
                ['work',     'steps'],
                ['features', 'items'],
                ['cta',      'buttons'],
                ['footer',   'social_links'],
                ['footer',   'quick_links'],
            ] as [$section, $field]) {
                if (isset($data[$section][$field]) && is_array($data[$section][$field])) {
                    $data[$section][$field] = array_values($data[$section][$field]);
                }
            }

            foreach (['banner', 'about', 'work', 'features', 'cta'] as $key) {
                if (isset($data[$key])) {
                    HomeSection::setSection($key, $data[$key]);
                }
            }

            if (isset($data['footer'])) {
                HomeSection::setSection('footer_settings', $data['footer']);
            }

            Notification::make()->title('Home page saved.')->success()->send();
        } catch (Halt $exception) {
            return;
        }
    }
}
