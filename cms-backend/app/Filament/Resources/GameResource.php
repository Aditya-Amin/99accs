<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\MediaImagePicker;
use App\Filament\Resources\GameResource\Pages;
use App\Models\Game;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationGroup = 'Products';

    protected static ?int $navigationSort = 2;

    // ─── Form ─────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(Game::class, 'slug', ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('URL-safe identifier, e.g. valorant'),

                Forms\Components\Grid::make(2)
                    ->schema([
                        MediaImagePicker::make('icon')
                            ->label('Icon')
                            ->previewWidth(300)
                            ->previewHeight(200)
                            ->columnSpan(2),

                        \Awcodes\Curator\Components\Forms\CuratorPicker::make('icon')
                            ->label('Select icon')
                            ->buttonLabel('Open Media Library')
                            ->live()
                            ->dehydrated()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->columnSpan(1),
                    ]),
            ])
            ->columns(2);
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->label('Icon')
                    ->getStateUsing(fn ($record): ?string => self::resolveMediaUrl($record->icon))
                    ->height(48)
                    ->width(48),

                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->weight(\Filament\Support\Enums\FontWeight::Medium),

                Tables\Columns\TextColumn::make('slug')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->alignCenter()
                    ->sortable(),
            ])
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

    private static function resolveMediaUrl(mixed $val): ?string
    {
        if (is_array($val)) {
            $val = $val[0] ?? null;
        }
        if (!$val) return null;
        if (ctype_digit((string) $val)) {
            return \App\Models\CuratorMedia::find((int) $val)?->url;
        }
        return (string) $val;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGames::route('/'),
            'create' => Pages\CreateGame::route('/create'),
            'edit'   => Pages\EditGame::route('/{record}/edit'),
        ];
    }
}
