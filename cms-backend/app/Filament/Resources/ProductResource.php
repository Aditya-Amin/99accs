<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\AccountType;
use App\Models\Product;
use App\Models\Section;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Products';

    protected static ?int $navigationSort = 1;

    // ─── Form ─────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ── Main column (tabs) ──────────────────────────────────────
                Forms\Components\Tabs::make('product_tabs')
                    ->tabs([

                        // Tab 1 — General
                        Forms\Components\Tabs\Tab::make('General')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Product Title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                                        $operation === 'create'
                                            ? $set('slug', \Illuminate\Support\Str::slug($state))
                                            : null
                                    ),

                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Product::class, 'slug', ignoreRecord: true),

                                Forms\Components\Repeater::make('highlights')
                                    ->label('Product Highlights')
                                    ->helperText('Icon + text bullets shown in the purchase sidebar.')
                                    ->schema([
                                        \Awcodes\Curator\Components\Forms\CuratorPicker::make('icon')
                                            ->label('Icon')
                                            ->helperText('Pick an icon from the media library.')
                                            ->listDisplay()
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('label')
                                            ->label('Highlight Text')
                                            ->required()
                                            ->placeholder('e.g. Instant Delivery After Payment')
                                            ->maxLength(255)
                                            ->columnSpan(2),
                                    ])
                                    ->columns(3)
                                    ->addActionLabel('+ Add Highlight')
                                    ->defaultItems(0)
                                    ->reorderableWithButtons()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                    ->columnSpanFull(),

                                Forms\Components\RichEditor::make('description')
                                    ->label('Full Description')
                                    ->helperText('Long product description rendered below the product details section.')
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Guarantee')
                                    ->schema([
                                        Forms\Components\TextInput::make('guarantee_title')
                                            ->label('Title')
                                            ->placeholder('e.g. 99Accs Guarantee')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('guarantee_body')
                                            ->label('Body')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        // Tab 2 — Game Details
                        Forms\Components\Tabs\Tab::make('Game Details')
                            ->icon('heroicon-m-puzzle-piece')
                            ->schema([
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('import_game_api')
                                        ->label('Import from Game API')
                                        ->icon('heroicon-m-cloud-arrow-down')
                                        ->color('primary')
                                        ->modalHeading('Import Product Data from Game API')
                                        ->modalDescription('Enter the account credentials. Data will be fetched and auto-filled into the form.')
                                        ->modalWidth('lg')
                                        ->form([
                                            Forms\Components\Select::make('game_override')
                                                ->label('Game')
                                                ->options(\App\Models\Game::orderBy('sort_order')->pluck('name', 'slug'))
                                                ->required()
                                                ->native(false)
                                                ->default(fn (Forms\Get $get) => $get('game_slug_hint')),

                                            Forms\Components\TextInput::make('username')
                                                ->label('Account Username')
                                                ->required()
                                                ->autocomplete('off'),

                                            Forms\Components\TextInput::make('password')
                                                ->label('Account Password')
                                                ->password()
                                                ->required()
                                                ->autocomplete('new-password'),

                                            Forms\Components\TextInput::make('proxy')
                                                ->label('Proxy (optional)')
                                                ->placeholder('host:port:user:pass')
                                                ->helperText('Residential proxy recommended. Format: host:port:username:password'),
                                        ])
                                        ->action(function (array $data, Forms\Get $get, Forms\Set $set) {
                                            $gameSlug = $data['game_override'] ?? null;

                                            try {
                                                $dto = app(\App\Services\GameApi\GameApiService::class)->import(
                                                    $gameSlug,
                                                    $data['username'],
                                                    $data['password'],
                                                    $data['proxy'] ?? null,
                                                );

                                                foreach ($dto->toFormArray() as $field => $value) {
                                                    $set($field, $value);
                                                }

                                                // Auto-fill game_id if not already set
                                                if (! $get('game_id') && $gameSlug) {
                                                    $game = \App\Models\Game::where('slug', $gameSlug)->first();
                                                    if ($game) {
                                                        $set('game_id', $game->id);
                                                    }
                                                }

                                                \Filament\Notifications\Notification::make()
                                                    ->title('Import successful')
                                                    ->body(implode(' · ', array_filter([
                                                        $dto->agents_count ? "{$dto->agents_count} Agents" : null,
                                                        count($dto->skins) ? count($dto->skins) . ' Skins' : null,
                                                        count($dto->buddies) ? count($dto->buddies) . ' Buddies' : null,
                                                    ])))
                                                    ->success()
                                                    ->send();
                                            } catch (\Throwable $e) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Import failed')
                                                    ->body($e->getMessage())
                                                    ->danger()
                                                    ->persistent()
                                                    ->send();
                                            }
                                        }),
                                ])
                                ->columnSpanFull(),

                                Forms\Components\Select::make('game_id')
                                    ->label('Game')
                                    ->relationship('game', 'name')
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set) {
                                        $set('account_type_id', null);
                                        $set('section_id', null);
                                    }),

                                Forms\Components\Select::make('account_type_id')
                                    ->label('Account Type')
                                    ->options(function (Forms\Get $get) {
                                        $gameId = $get('game_id');
                                        $q = AccountType::orderBy('sort_order');
                                        if ($gameId) {
                                            $q->where('game_id', $gameId);
                                        }
                                        return $q->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->native(false),

                                Forms\Components\Select::make('section_id')
                                    ->label('Catalog Section')
                                    ->options(function (Forms\Get $get) {
                                        $gameId = $get('game_id');
                                        $q = Section::orderBy('sort_order');
                                        if ($gameId) {
                                            $q->where('game_id', $gameId);
                                        }
                                        return $q->pluck('label', 'id');
                                    })
                                    ->required()
                                    ->native(false)
                                    ->helperText('Which header group this product appears under on the shop page.'),

                                Forms\Components\TextInput::make('rank')
                                    ->label('Rank')
                                    ->placeholder('e.g. Diamond 2, Gold 1')
                                    ->maxLength(100),

                                Forms\Components\Toggle::make('has_gallery')
                                    ->label('Has Gallery Popup')
                                    ->helperText('Shows image-stack icon + count overlay on card thumbnail.'),

                                Forms\Components\TagsInput::make('agents')
                                    ->label('Agents')
                                    ->placeholder('Add agent name...')
                                    ->columnSpanFull(),

                                Forms\Components\TagsInput::make('skins')
                                    ->label('Skins')
                                    ->placeholder('Add skin name...')
                                    ->columnSpanFull(),

                                Forms\Components\TagsInput::make('buddies')
                                    ->label('Buddies')
                                    ->placeholder('Add buddy name...')
                                    ->columnSpanFull(),

                                Forms\Components\Repeater::make('feature_badges')
                                    ->label('Feature Badges (card chips)')
                                    ->schema([
                                        Forms\Components\TextInput::make('label')
                                            ->required()
                                            ->placeholder('e.g. 57 Skins'),

                                        Forms\Components\Select::make('icon')
                                            ->label('Icon')
                                            ->options(Product::BADGE_ICONS)
                                            ->required()
                                            ->native(false),
                                    ])
                                    ->columns(2)
                                    ->maxItems(4)
                                    ->defaultItems(0)
                                    ->addActionLabel('Add badge')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        // Tab 3 — Pricing
                        Forms\Components\Tabs\Tab::make('Pricing')
                            ->icon('heroicon-m-currency-dollar')
                            ->schema([
                                Forms\Components\TextInput::make('regular_price')
                                    ->label('Regular Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->helperText('Original price — shown as strikethrough when a Sale Price is set.'),

                                Forms\Components\TextInput::make('price')
                                    ->label('Sale Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),

                                \Awcodes\Curator\Components\Forms\CuratorPicker::make('badge_icon')
                                    ->label('Badge Icon')
                                    ->helperText('Small icon shown next to the region code on product cards and detail page.')
                                    ->listDisplay(true)
                                    ->constrained(true)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        // Tab 4 — Inventory
                        Forms\Components\Tabs\Tab::make('Inventory')
                            ->icon('heroicon-m-archive-box')
                            ->schema([
                                Forms\Components\TextInput::make('stock_qty')
                                    ->label('Stock')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0),

                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('min_quantity')
                                    ->label('Minimum Order Quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->helperText('Used by simple_description layout (page 2).'),

                                Forms\Components\TextInput::make('last_match_label')
                                    ->label('Last Match Label')
                                    ->placeholder('Last Match: August 2024')
                                    ->helperText('Chip shown above price on Fortnite locker detail page.'),
                            ])
                            ->columns(2),

                        // Tab 5 — Detail Data (game API import fills these)
                        Forms\Components\Tabs\Tab::make('Detail Data')
                            ->icon('heroicon-m-code-bracket')
                            ->schema([
                                Forms\Components\Placeholder::make('detail_note')
                                    ->label('')
                                    ->content('These fields are populated automatically by the Game API import (Phase 4). You can inspect or override values here as raw JSON.')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('source_provider')
                                    ->label('Source Provider')
                                    ->placeholder('riot | epic | null')
                                    ->maxLength(32),

                                Forms\Components\TextInput::make('external_id')
                                    ->label('External ID')
                                    ->maxLength(255),

                                Forms\Components\Textarea::make('profile_info')
                                    ->label('Profile Info (JSON)')
                                    ->rows(4)
                                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                                    ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('agents_detailed')
                                    ->label('Agents Detailed (JSON)')
                                    ->rows(4)
                                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                                    ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('agents_count')
                                    ->label('Agents Count')
                                    ->numeric(),

                                Forms\Components\Textarea::make('skin_inventory')
                                    ->label('Skin Inventory (JSON)')
                                    ->rows(4)
                                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                                    ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('skin_filters')
                                    ->label('Skin Filters (JSON)')
                                    ->rows(4)
                                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                                    ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('buddy_inventory')
                                    ->label('Buddy Inventory (JSON)')
                                    ->rows(4)
                                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                                    ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('account_level')
                                    ->label('Account Level (JSON)')
                                    ->rows(3)
                                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                                    ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('account_stats')
                                    ->label('Account Stats (JSON)')
                                    ->rows(4)
                                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                                    ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('locker')
                                    ->label('Locker (JSON)')
                                    ->rows(6)
                                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                                    ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('seasons')
                                    ->label('Seasons (JSON)')
                                    ->rows(4)
                                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                                    ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                                    ->columnSpanFull(),

                                Forms\Components\Repeater::make('faq_items')
                                    ->label('FAQ')
                                    ->helperText('Frequently asked questions shown at the bottom of the product detail page.')
                                    ->schema([
                                        Forms\Components\TextInput::make('question')
                                            ->label('Question')
                                            ->required()
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('answer')
                                            ->label('Answer')
                                            ->required()
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ])
                                    ->defaultItems(0)
                                    ->addActionLabel('Add FAQ item')
                                    ->collapsible()
                                    ->cloneable()
                                    ->columnSpanFull(),

                            ])
                            ->columns(2),

                    ])
                    ->columnSpan(['lg' => 2]),

                // ── Sidebar ─────────────────────────────────────────────────
                Forms\Components\Group::make()
                    ->schema([

                        Forms\Components\Section::make('Status')
                            ->schema([
                                Forms\Components\Toggle::make('is_visible')
                                    ->label('Published')
                                    ->default(true),
                            ]),

                        Forms\Components\Section::make('Region')
                            ->description('Regions this product is sold in. The card badge (NA, EU…) and country flag come from the first selected region.')
                            ->schema([
                                Forms\Components\Select::make('regions')
                                    ->label('Region(s)')
                                    ->relationship('regions', 'name')
                                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                                        $record->code ? "{$record->name} ({$record->code})" : $record->name
                                    )
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('Skin Type')
                            ->description('Select the skin type for this product (e.g. NFA Guaranteed Skins, NFA Random Skins).')
                            ->schema([
                                Forms\Components\Select::make('skinTags')
                                    ->label('Skin Type')
                                    ->relationship('skinTags', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Type Name')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                                                $set('slug', \Illuminate\Support\Str::slug($state))
                                            ),
                                        Forms\Components\TextInput::make('slug')
                                            ->required(),
                                        Forms\Components\Select::make('game_id')
                                            ->label('Game')
                                            ->relationship('game', 'name')
                                            ->nullable()
                                            ->native(false),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('Media')
                            ->schema([
                                // Shown on edit when no Curator thumbnail is set yet —
                                // lets the user see the legacy Spatie/JSON image before replacing it.
                                Forms\Components\Placeholder::make('legacy_thumbnail_preview')
                                    ->label('Current Thumbnail')
                                    ->helperText('This image comes from a legacy upload. Use the Thumbnail picker below to replace it with a Curator-managed image.')
                                    ->content(function ($record): \Illuminate\Contracts\Support\Htmlable {
                                        $src = $record?->getFirstMediaUrl('product_featured_image');
                                        if (! $src) {
                                            $imgs = is_array($record?->images) ? $record->images : [];
                                            $src  = $imgs[0] ?? null;
                                        }
                                        if (! $src) {
                                            return new \Illuminate\Support\HtmlString('<span style="color:#6b7280;font-size:13px;">No image on record.</span>');
                                        }
                                        return new \Illuminate\Support\HtmlString(
                                            '<img src="' . e($src) . '" style="height:96px;border-radius:8px;object-fit:cover;border:1px solid rgba(0,0,0,.1);" />'
                                        );
                                    })
                                    ->visible(fn ($record) => $record && ! $record->featured_image_id)
                                    ->columnSpanFull(),

                                \Awcodes\Curator\Components\Forms\CuratorPicker::make('featured_image_id')
                                    ->label('Thumbnail')
                                    ->helperText('Main product image shown on cards and in the detail header.')
                                    ->columnSpanFull(),

                                \Awcodes\Curator\Components\Forms\CuratorPicker::make('gallery_ids')
                                    ->label('Gallery Images')
                                    ->helperText('Additional images shown in the gallery popup.')
                                    ->multiple()
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('images')
                                    ->label('Image URLs (JSON array — seeded / API-imported products)')
                                    ->rows(4)
                                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                                    ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                                    ->helperText('Seeded/imported image paths. Curator picks above take priority in the API.')
                                    ->columnSpanFull(),
                            ]),

                    ])
                    ->columnSpan(['lg' => 1]),

            ])
            ->columns(3);
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Thumbnail — Curator → Spatie media → JSON images fallback
                Tables\Columns\ImageColumn::make('thumbnail_url')
                    ->label('')
                    ->getStateUsing(function (Product $record): ?string {
                        if ($record->featured_image_id) {
                            $media = \App\Models\CuratorMedia::find($record->featured_image_id);
                            if ($media?->url) return $media->url;
                        }
                        $spatie = $record->getFirstMediaUrl('product_featured_image');
                        if ($spatie) return $spatie;
                        $imgs = is_array($record->images) ? $record->images : [];
                        return $imgs[0] ?? null;
                    })
                    ->square()
                    ->size(52)
                    ->defaultImageUrl(fn () => 'https://placehold.co/52x52/1f2937/1f2937'),

                // Product name + SKU sub-line; tooltip shows full name when truncated
                Tables\Columns\TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn (Product $record): ?string =>
                        mb_strlen($record->name) > 50 ? $record->name : null
                    )
                    ->description(fn (Product $record): ?string => $record->sku ?: null)
                    ->wrap(),

                Tables\Columns\TextColumn::make('game.name')
                    ->label('Game')
                    ->badge()
                    ->color(fn (Product $record): string => match ($record->game?->slug) {
                        Product::GAME_VALORANT => 'danger',
                        Product::GAME_FORTNITE => 'warning',
                        Product::GAME_LEGENDS  => 'success',
                        default                => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('accountType.name')
                    ->label('Type')
                    ->badge()
                    ->color(fn (Product $record): string => match ($record->accountType?->slug) {
                        Product::ACCOUNT_VERIFIED           => 'primary',
                        Product::ACCOUNT_INACTIVE_EXCLUSIVE => 'danger',
                        Product::ACCOUNT_NFA                => 'warning',
                        Product::ACCOUNT_NFA_INACTIVE       => 'gray',
                        Product::ACCOUNT_STANDARD           => 'success',
                        default                             => 'gray',
                    })
                    ->sortable(),

                // Regions displayed as stacked badges (one per region code)
                Tables\Columns\TextColumn::make('regions.code')
                    ->label('Region')
                    ->badge()
                    ->color('info')
                    ->separator(',')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_qty')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                // Inline toggle — lets admin publish/unpublish without opening edit
                Tables\Columns\ToggleColumn::make('is_visible')
                    ->label('Live')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('game')
                    ->relationship('game', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('account_type')
                    ->label('Account Type')
                    ->relationship('accountType', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('regions')
                    ->label('Region')
                    ->relationship('regions', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('section')
                    ->label('Section')
                    ->relationship('section', 'label')
                    ->preload(),

                Tables\Filters\SelectFilter::make('skinTags')
                    ->label('Skin Type')
                    ->relationship('skinTags', 'name')
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Published')
                    ->trueLabel('Published only')
                    ->falseLabel('Hidden only'),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->tooltip('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip('Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) =>
                            $records->each->update(['is_visible' => true])
                        )
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('Unpublish Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) =>
                            $records->each->update(['is_visible' => false])
                        )
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

}
