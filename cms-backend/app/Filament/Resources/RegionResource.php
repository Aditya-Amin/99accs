<?php

namespace App\Filament\Resources;

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

                \Awcodes\Curator\Components\Forms\CuratorPicker::make('flag')
                    ->label('Country Flag'),

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
                    ->getStateUsing(fn ($record): ?string => self::resolveMediaUrl($record->flag))
                    ->height(40)
                    ->width(60),

                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->weight(\Filament\Support\Enums\FontWeight::Medium),

                Tables\Columns\TextColumn::make('code')
                    ->label('Badge')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

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
        if (!$val) return null;
        if (ctype_digit((string) $val)) {
            return \App\Models\CuratorMedia::find((int) $val)?->url;
        }
        return \Illuminate\Support\Facades\Storage::disk('public')->url($val);
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
