<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Page Info')->schema([
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
                        ->unique(Page::class, 'slug', ignoreRecord: true)
                        ->helperText('URL: /pages/{slug}'),
                ])->columns(2),

                Forms\Components\Section::make('Content Blocks')->schema([
                    Forms\Components\Builder::make('blocks')
                        ->label('')
                        ->blocks(static::blockTypes())
                        ->collapsible()
                        ->cloneable()
                        ->columnSpanFull(),
                ]),

                Forms\Components\Section::make('SEO')->schema([
                    Forms\Components\TextInput::make('meta_title')->maxLength(255),
                    Forms\Components\Textarea::make('meta_description')->rows(2),
                    Forms\Components\TextInput::make('og_image')->label('OG Image URL'),
                ])->columns(1)->collapsed(),

            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Status')->schema([
                    Forms\Components\Toggle::make('is_published')
                        ->label('Published')
                        ->default(false),
                ]),
            ])->columnSpan(['lg' => 1]),

        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable(),
                Tables\Columns\IconColumn::make('is_published')->boolean()->label('Published'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit'   => Pages\EditPage::route('/{record}/edit'),
        ];
    }

    // ─── Block types ──────────────────────────────────────────────────────────

    private static function blockTypes(): array
    {
        return [
            Forms\Components\Builder\Block::make('hero')
                ->label('Hero / Banner')
                ->schema([
                    Forms\Components\TextInput::make('title')->required(),
                    Forms\Components\TextInput::make('subtitle'),
                    Forms\Components\Textarea::make('description')->rows(2),
                    Forms\Components\TextInput::make('background_image')->label('Background Image URL'),
                    Forms\Components\TextInput::make('cta_label')->label('CTA Button Label'),
                    Forms\Components\TextInput::make('cta_url')->label('CTA Button URL'),
                ])->columns(2),

            Forms\Components\Builder\Block::make('rich_text')
                ->label('Rich Text')
                ->schema([
                    Forms\Components\RichEditor::make('content')->required()->columnSpanFull(),
                ]),

            Forms\Components\Builder\Block::make('two_column')
                ->label('Two Column Text')
                ->schema([
                    Forms\Components\RichEditor::make('left')->label('Left Column'),
                    Forms\Components\RichEditor::make('right')->label('Right Column'),
                ])->columns(2),

            Forms\Components\Builder\Block::make('image_text')
                ->label('Image + Text')
                ->schema([
                    Forms\Components\TextInput::make('image')->label('Image URL')->required(),
                    Forms\Components\TextInput::make('heading')->required(),
                    Forms\Components\Textarea::make('body')->rows(3),
                    Forms\Components\Select::make('alignment')
                        ->options(['left' => 'Image Left', 'right' => 'Image Right'])
                        ->default('left')
                        ->native(false),
                ])->columns(2),

            Forms\Components\Builder\Block::make('features_grid')
                ->label('Features Grid')
                ->schema([
                    Forms\Components\TextInput::make('title'),
                    Forms\Components\Repeater::make('items')
                        ->schema([
                            Forms\Components\TextInput::make('title')->required(),
                            Forms\Components\TextInput::make('icon')->placeholder('/img/icons/feature01.png'),
                            Forms\Components\Textarea::make('text')->rows(2),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Builder\Block::make('faq')
                ->label('FAQ Block')
                ->schema([
                    Forms\Components\TextInput::make('title')->default('Frequently Asked Questions'),
                    Forms\Components\Repeater::make('items')
                        ->schema([
                            Forms\Components\TextInput::make('question')->required(),
                            Forms\Components\Textarea::make('answer')->rows(2)->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Builder\Block::make('stats')
                ->label('Stats Bar')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->schema([
                            Forms\Components\TextInput::make('label')->required(),
                            Forms\Components\TextInput::make('value')->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Builder\Block::make('cta_section')
                ->label('Call to Action')
                ->schema([
                    Forms\Components\TextInput::make('title')->required(),
                    Forms\Components\Textarea::make('description')->rows(2),
                    Forms\Components\TextInput::make('button_label')->default('Get Started'),
                    Forms\Components\TextInput::make('button_url'),
                    Forms\Components\TextInput::make('background_image'),
                ])->columns(2),

            Forms\Components\Builder\Block::make('product_carousel')
                ->label('Product Carousel')
                ->schema([
                    Forms\Components\TextInput::make('title'),
                    Forms\Components\Select::make('game')
                        ->options(['valorant' => 'Valorant', 'fortnite' => 'Fortnite', 'legends' => 'League of Legends', '' => 'All Games'])
                        ->default('')
                        ->native(false),
                    Forms\Components\TextInput::make('limit')->numeric()->default(8),
                ])->columns(3),
        ];
    }
}
