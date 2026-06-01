<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FooterWidgetResource\Pages;
use App\Models\FooterWidget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FooterWidgetResource extends Resource
{
    protected static ?string $model = FooterWidget::class;

    protected static ?string $navigationIcon  = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Footer Widgets';
    protected static ?int    $navigationSort  = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Widget Settings')->schema([
                Forms\Components\Select::make('type')
                    ->options(['menu' => 'Menu Widget', 'help_cta' => 'Help / CTA Widget'])
                    ->required()
                    ->native(false)
                    ->live()
                    ->helperText('Menu: a list of links with an icon title. Help CTA: a description + button.'),

                Forms\Components\TextInput::make('col_class')
                    ->label('Bootstrap Column Class')
                    ->required()
                    ->default('col-lg-3 col-6')
                    ->placeholder('col-lg-3 col-6')
                    ->helperText('Controls width in the footer grid. e.g. col-lg-2 col-6, col-lg-4 col-sm-6'),

                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->helperText('Lower numbers appear first (left).'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ])->columns(2),

            // ── Menu widget fields ──────────────────────────────────────────────
            Forms\Components\Section::make('Menu Widget')
                ->visible(fn (Forms\Get $get) => $get('type') === 'menu')
                ->schema([
                    Forms\Components\TextInput::make('config.title')
                        ->label('Widget Title')
                        ->required()
                        ->placeholder('Valorant'),

                    \Awcodes\Curator\Components\Forms\CuratorPicker::make('config.icon_url')
                        ->label('Title Icon')
                        ->helperText('Small icon shown next to the widget heading. Leave blank for none.')
                        ->columnSpanFull(),

                    Forms\Components\Repeater::make('config.links')
                        ->label('Menu Links')
                        ->schema([
                            Forms\Components\TextInput::make('label')
                                ->required()
                                ->placeholder('Valorant - NA'),
                            Forms\Components\TextInput::make('href')
                                ->required()
                                ->placeholder('/shop/valorant?region=na'),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->reorderable()
                        ->addActionLabel('Add Link')
                        ->columnSpanFull(),
                ])->columns(2),

            // ── Help CTA widget fields ──────────────────────────────────────────
            Forms\Components\Section::make('Help CTA Widget')
                ->visible(fn (Forms\Get $get) => $get('type') === 'help_cta')
                ->schema([
                    Forms\Components\TextInput::make('config.title')
                        ->label('Title')
                        ->required()
                        ->placeholder('Need Help?')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('config.description')
                        ->label('Description')
                        ->rows(3)
                        ->placeholder("We're here to help. Our expert human-support team is at your service 24/7.")
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('config.button_label')
                        ->label('Button Label')
                        ->placeholder('Create ticket'),

                    Forms\Components\TextInput::make('config.button_href')
                        ->label('Button URL')
                        ->placeholder('/support/contact'),
                ])->columns(2),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'menu'     => 'primary',
                        'help_cta' => 'success',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'menu'     => 'Menu',
                        'help_cta' => 'Help CTA',
                        default    => $state,
                    }),

                Tables\Columns\TextColumn::make('config.title')
                    ->label('Title')
                    ->getStateUsing(fn ($record) => $record->config['title'] ?? '—')
                    ->weight(\Filament\Support\Enums\FontWeight::Medium),

                Tables\Columns\TextColumn::make('col_class')
                    ->label('Column')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
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
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFooterWidgets::route('/'),
            'create' => Pages\CreateFooterWidget::route('/create'),
            'edit'   => Pages\EditFooterWidget::route('/{record}/edit'),
        ];
    }
}
