<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportArticleResource\Pages;
use App\Models\SupportArticle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportArticleResource extends Resource
{
    protected static ?string $model = SupportArticle::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 3;

    protected static ?string $label = 'Support Article';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\TextInput::make('title')
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
                        ->unique(SupportArticle::class, 'slug', ignoreRecord: true)
                        ->maxLength(255),

                    Forms\Components\Textarea::make('excerpt')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\RichEditor::make('content')
                        ->required()
                        ->columnSpanFull(),
                ])->columns(2),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Settings')->schema([
                    Forms\Components\TextInput::make('category')
                        ->default('general')
                        ->required()
                        ->helperText('e.g. account, payment, delivery'),

                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),

                    Forms\Components\Toggle::make('is_published')
                        ->label('Published')
                        ->default(true),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->limit(50),
                Tables\Columns\TextColumn::make('category')->badge()->sortable(),
                Tables\Columns\TextColumn::make('sort_order')->label('Order')->sortable(),
                Tables\Columns\IconColumn::make('is_published')->boolean()->label('Published'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([Tables\Filters\SelectFilter::make('category')])
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
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSupportArticles::route('/'),
            'create' => Pages\CreateSupportArticle::route('/create'),
            'edit'   => Pages\EditSupportArticle::route('/{record}/edit'),
        ];
    }
}
