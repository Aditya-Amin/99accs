<?php

namespace App\Filament\Resources;

use App\Filament\Forms\IconUpload;
use App\Filament\Resources\RegionResource\Pages;
use App\Models\Region;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Products';

    protected static ?int $navigationSort = 5;

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
                    ->unique(Region::class, 'slug', ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('URL-safe identifier, e.g. na, eu, apac'),

                Forms\Components\TextInput::make('code')
                    ->label('Country Code Badge')
                    ->required()
                    ->maxLength(10)
                    ->helperText('Short badge text rendered on product cards (NA, EU, AP, EUW, LAS, TR…).'),

                Forms\Components\TextInput::make('class_modifier')
                    ->label('Country CSS Class Override')
                    ->placeholder('ap')
                    ->maxLength(50)
                    ->helperText('Only set when CSS class differs from code.toLowerCase() — e.g. LAS cards use class "ap".'),

                IconUpload::make('flag', 'Country Flag', 'country-flags'),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
            ])
            ->columns(2);
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('flag')
                    ->label('Flag')
                    ->disk('public')
                    ->height(48),

                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Badge')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRegions::route('/'),
            'create' => Pages\CreateRegion::route('/create'),
            'edit'   => Pages\EditRegion::route('/{record}/edit'),
        ];
    }
}
