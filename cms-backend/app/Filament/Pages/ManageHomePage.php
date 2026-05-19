<?php

namespace App\Filament\Pages;

use App\Models\HomeSection;
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
        $this->form->fill([
            'banner'   => HomeSection::getSection('banner'),
            'about'    => HomeSection::getSection('about'),
            'work'     => HomeSection::getSection('work'),
            'features' => HomeSection::getSection('features'),
            'cta'      => HomeSection::getSection('cta'),
        ]);
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
                                Forms\Components\TextInput::make('banner.background_image')
                                    ->label('Background Image URL')
                                    ->placeholder('/img/bg/hero_bg.jpg')
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('banner.subtitle.icon')
                                    ->label('Subtitle Icon')
                                    ->options(['shield_check' => 'Shield Check', 'trophy' => 'Trophy', 'delivery' => 'Delivery', 'warranty' => 'Warranty'])
                                    ->native(false),

                                Forms\Components\TextInput::make('banner.subtitle.text')
                                    ->label('Subtitle Text'),

                                Forms\Components\TextInput::make('banner.heading.prefix')
                                    ->label('Heading Prefix'),

                                Forms\Components\TextInput::make('banner.heading.highlight')
                                    ->label('Heading Highlight (accent)'),

                                Forms\Components\TextInput::make('banner.heading.suffix')
                                    ->label('Heading Suffix'),

                                Forms\Components\Textarea::make('banner.description')
                                    ->label('Description')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Forms\Components\Repeater::make('banner.features')
                                    ->label('Feature Chips')
                                    ->schema([
                                        Forms\Components\Select::make('icon')
                                            ->options(['trophy' => 'Trophy', 'delivery' => 'Delivery', 'warranty' => 'Warranty', 'shield_check' => 'Shield'])
                                            ->native(false)->required(),
                                        Forms\Components\TextInput::make('text')->required(),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->columnSpanFull(),

                                Forms\Components\Repeater::make('banner.categories')
                                    ->label('Game Category Cards')
                                    ->schema([
                                        Forms\Components\TextInput::make('alt')->required()->label('Game Name'),
                                        Forms\Components\TextInput::make('href')->required()->label('Link URL'),
                                        Forms\Components\TextInput::make('image')->required()->label('Image URL'),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(0)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        // ── About ────────────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('About')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Forms\Components\TextInput::make('about.background_image')
                                    ->label('Background Image URL')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('about.image')
                                    ->label('Section Image URL'),

                                Forms\Components\TextInput::make('about.title')
                                    ->label('Section Title'),

                                Forms\Components\Repeater::make('about.paragraphs')
                                    ->label('Paragraphs')
                                    ->simple(Forms\Components\Textarea::make('')->rows(2))
                                    ->defaultItems(0)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('about.stats.happy_customers')
                                    ->label('Happy Customers (counter)')
                                    ->numeric(),

                                Forms\Components\TextInput::make('about.stats.accounts_sold')
                                    ->label('Accounts Sold (counter)')
                                    ->numeric(),
                            ])
                            ->columns(2),

                        // ── How it Works ─────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('How it Works')
                            ->icon('heroicon-m-list-bullet')
                            ->schema([
                                Forms\Components\TextInput::make('work.background_image')
                                    ->label('Background Image URL')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('work.title')
                                    ->label('Section Title')
                                    ->columnSpanFull(),

                                Forms\Components\Repeater::make('work.steps')
                                    ->label('Steps')
                                    ->schema([
                                        Forms\Components\TextInput::make('num')->label('Number')->placeholder('01')->maxLength(4),
                                        Forms\Components\TextInput::make('title')->required(),
                                        Forms\Components\Textarea::make('text')->rows(2)->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->columnSpanFull(),

                                Forms\Components\TagsInput::make('work.images')
                                    ->label('Step Images (URLs)')
                                    ->columnSpanFull(),
                            ]),

                        // ── Features ─────────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Features')
                            ->icon('heroicon-m-star')
                            ->schema([
                                Forms\Components\TextInput::make('features.background_image')
                                    ->label('Background Image URL')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('features.heading.prefix')
                                    ->label('Heading Prefix'),

                                Forms\Components\TextInput::make('features.heading.user_count')
                                    ->label('User Count')
                                    ->numeric(),

                                Forms\Components\TextInput::make('features.heading.suffix')
                                    ->label('Heading Suffix')
                                    ->columnSpanFull(),

                                Forms\Components\Repeater::make('features.items')
                                    ->label('Feature Items')
                                    ->schema([
                                        Forms\Components\TextInput::make('title')->required(),
                                        \App\Filament\Forms\IconUpload::make('icon', 'Icon', 'feature-icons'),
                                        Forms\Components\Textarea::make('text')->rows(2)->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        // ── CTA ──────────────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('CTA')
                            ->icon('heroicon-m-megaphone')
                            ->schema([
                                Forms\Components\TextInput::make('cta.background_image')
                                    ->label('Background Image URL')
                                    ->columnSpanFull(),

                                Forms\Components\TagsInput::make('cta.title_lines')
                                    ->label('Title Lines (one per line)')
                                    ->columnSpanFull(),

                                Forms\Components\Repeater::make('cta.buttons')
                                    ->label('Buttons')
                                    ->schema([
                                        Forms\Components\Select::make('platform')
                                            ->options(['telegram' => 'Telegram', 'discord' => 'Discord', 'twitter' => 'Twitter', 'youtube' => 'YouTube'])
                                            ->native(false)->required(),
                                        Forms\Components\TextInput::make('label')->required(),
                                        Forms\Components\TextInput::make('url')->required(),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(0)
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

            foreach (['banner', 'about', 'work', 'features', 'cta'] as $key) {
                if (isset($data[$key])) {
                    HomeSection::setSection($key, $data[$key]);
                }
            }

            Notification::make()->title('Home page saved.')->success()->send();
        } catch (Halt $exception) {
            return;
        }
    }
}
