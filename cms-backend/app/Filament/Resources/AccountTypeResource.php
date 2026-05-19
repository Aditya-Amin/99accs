<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountTypeResource\Pages;
use App\Models\AccountType;
use App\Models\Game;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountTypeResource extends Resource
{
    protected static ?string $model = AccountType::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Products';

    protected static ?int $navigationSort = 3;

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
                    ->unique(AccountType::class, 'slug', ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('URL-safe identifier, e.g. inactive_exclusive'),

                Forms\Components\Select::make('game_id')
                    ->label('Game')
                    ->options(Game::orderBy('sort_order')->pluck('name', 'id'))
                    ->nullable()
                    ->native(false),

                Forms\Components\Select::make('detail_layout')
                    ->label('Detail Layout')
                    ->options([
                        'simple_two'    => 'Simple Two (simple_two)',
                        'simple_three'  => 'Simple Three (simple_three)',
                        'rich'          => 'Rich (rich)',
                        'fortnite_four' => 'Fortnite Four (fortnite_four)',
                    ])
                    ->default('simple_two')
                    ->required()
                    ->native(false),

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
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('slug')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('game.name')
                    ->label('Game')
                    ->sortable(),

                Tables\Columns\TextColumn::make('detail_layout')
                    ->label('Layout')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'rich'          => 'danger',
                        'fortnite_four' => 'warning',
                        'simple_three'  => 'success',
                        default         => 'gray',
                    }),

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
            'index'  => Pages\ListAccountTypes::route('/'),
            'create' => Pages\CreateAccountType::route('/create'),
            'edit'   => Pages\EditAccountType::route('/{record}/edit'),
        ];
    }
}
