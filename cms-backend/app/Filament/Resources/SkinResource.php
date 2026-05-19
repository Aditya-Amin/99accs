<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SkinResource\Pages;
use App\Models\Skin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SkinResource extends Resource
{
    protected static ?string $model = Skin::class;

    protected static ?string $navigationIcon  = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Products';
    protected static ?int    $navigationSort  = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Skin Type')
                ->description('A skin type groups products by the kind of skins included (e.g. "NFA Guaranteed Skins", "NFA Random Skins").')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Type Name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                            $operation === 'create'
                                ? $set('slug', Str::slug($state))
                                : null
                        ),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(Skin::class, 'slug', ignoreRecord: true),

                    Forms\Components\Select::make('game_id')
                        ->label('Game')
                        ->relationship('game', 'name')
                        ->nullable()
                        ->native(false)
                        ->placeholder('All games')
                        ->helperText('Restrict this skin type to a specific game, or leave blank for all.'),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0),

                    \App\Filament\Forms\IconUpload::make('image', 'Icon', 'skin-icons'),

                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('game.name')
                    ->label('Game')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Valorant'          => 'danger',
                        'Fortnite'          => 'warning',
                        'League of Legends' => 'success',
                        default             => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('game')
                    ->relationship('game', 'name')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index'  => Pages\ListSkins::route('/'),
            'create' => Pages\CreateSkin::route('/create'),
            'edit'   => Pages\EditSkin::route('/{record}/edit'),
        ];
    }
}
