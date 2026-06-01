<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuResource\Pages;
use App\Models\Menu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Menu Info')->schema([
                Forms\Components\TextInput::make('name')
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
                    ->unique(Menu::class, 'slug', ignoreRecord: true)
                    ->helperText('Used by Next.js: GET /api/v1/menus/{slug}  e.g. header, footer'),
            ])->columns(2),

            Forms\Components\Section::make('Menu Items')->schema([
                Forms\Components\Repeater::make('allItems')
                    ->label('')
                    ->relationship()
                    ->schema([
                        Forms\Components\TextInput::make('label')->required()->maxLength(100),
                        Forms\Components\TextInput::make('url')->required()->maxLength(500),
                        Forms\Components\Select::make('target')
                            ->options(['_self' => 'Same tab', '_blank' => 'New tab'])
                            ->default('_self')
                            ->native(false),
                        Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                        Forms\Components\Select::make('parent_id')
                            ->label('Parent Item')
                            ->options(fn (?Menu $record) =>
                                $record
                                    ? $record->allItems()->pluck('label', 'id')->toArray()
                                    : []
                            )
                            ->nullable()
                            ->native(false)
                            ->helperText('Leave empty for top-level item.'),
                    ])
                    ->columns(5)
                    ->defaultItems(0)
                    ->addActionLabel('Add menu item')
                    ->orderColumn('sort_order')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->badge(),
                Tables\Columns\TextColumn::make('allItems_count')
                    ->label('Items')
                    ->counts('allItems'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
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
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit'   => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
