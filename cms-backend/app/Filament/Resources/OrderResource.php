<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Orders';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Order Information')
                        ->schema([
                            Forms\Components\Select::make('customer_id')
                                ->relationship('customer', 'email')
                                ->required()
                                ->hiddenOn('edit'),
                            Forms\Components\TextInput::make('customer_email')
                                ->label('Customer')
                                ->afterStateHydrated(fn (Forms\Components\TextInput $component, $record) =>
                                    $component->state($record?->customer?->email ?? '')
                                )
                                ->readOnly()
                                ->dehydrated(false)
                                ->visibleOn('edit'),
                            Forms\Components\TextInput::make('number')
                                ->required()
                                ->default('ORD-' . random_int(10000, 99999))
                                ->maxLength(255),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Pending',
                                    'processing' => 'Processing',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->default('pending')
                                ->required(),
                             Forms\Components\Select::make('payment_status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                    'failed' => 'Failed',
                                ])
                                ->default('pending'),
                            Forms\Components\Select::make('payment_method')
                                ->options(function () {
                                    $gateways = \App\Models\PaymentGateway::where('is_active', true)->pluck('name', 'slug')->toArray();
                                    return array_merge(['offline' => 'Offline / Cash'], $gateways);
                                })
                                ->required(),
                        ])->columns(2),

                    Forms\Components\Section::make('Order Items')
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->relationship()
                                ->schema([
                                    Forms\Components\Select::make('product_id')
                                        ->relationship('product', 'name')
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            $product = \App\Models\Product::find($state);
                                            if ($product) {
                                                $set('price_snapshot', $product->price);
                                                $set('product_name_snapshot', $product->name);
                                            }
                                        }),
                                    Forms\Components\TextInput::make('product_name_snapshot')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('quantity')
                                        ->numeric()
                                        ->default(1)
                                        ->required()
                                        ->reactive(),
                                    Forms\Components\TextInput::make('price_snapshot')
                                        ->numeric()
                                        ->required()
                                        ->reactive(),
                                ])
                                ->columns(4)
                                ->columnSpanFull()
                                ->reactive()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    $items = collect($get('items'))->filter(fn($item) => !empty($item['price_snapshot']) && !empty($item['quantity']));
                                    $subtotal = $items->sum(fn($item) => (float)$item['price_snapshot'] * (int)$item['quantity']);
                                    
                                    $shipping = (float) $get('shipping_cost') ?? 0;
                                    $vat = (float) $get('vat_tax') ?? 0;
                                    $discount = (float) $get('discount_amount') ?? 0;

                                    $set('total_price', number_format($subtotal + $shipping + $vat - $discount, 2, '.', ''));
                                }),
                        ]),
                ])->columnSpan(2),

                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Financials')
                        ->schema([
                            Forms\Components\TextInput::make('shipping_cost')
                                ->label('Shipping Cost')
                                ->numeric()
                                ->default(0)
                                ->reactive()
                                ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateTotal($set, $get))
                                ->prefix('$'),
                            Forms\Components\TextInput::make('vat_tax')
                                ->label('VAT / Tax')
                                ->numeric()
                                ->default(0)
                                ->reactive()
                                ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateTotal($set, $get))
                                ->prefix('$'),
                            Forms\Components\TextInput::make('discount_amount')
                                ->label('Discount Amount')
                                ->numeric()
                                ->default(0)
                                ->reactive()
                                ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateTotal($set, $get))
                                ->prefix('$'),
                            Forms\Components\TextInput::make('coupon_code')
                                ->label('Coupon Code')
                                ->maxLength(255),
                            
                            Forms\Components\Placeholder::make('summary')
                                ->label('Order Summary')
                                ->content(function (Forms\Get $get) {
                                     $items = collect($get('items'))->filter(fn($item) => !empty($item['price_snapshot']) && !empty($item['quantity']));
                                     $subtotal = $items->sum(fn($item) => (float)$item['price_snapshot'] * (int)$item['quantity']);
                                     return "Subtotal: $" . number_format($subtotal, 2);
                                }),

                            Forms\Components\TextInput::make('total_price')
                                ->label('Grand Total')
                                ->readOnly()
                                ->numeric()
                                ->prefix('$'),
                        ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function updateTotal(Forms\Set $set, Forms\Get $get)
    {
        $items = collect($get('items'))->filter(fn($item) => !empty($item['price_snapshot']) && !empty($item['quantity']));
        $subtotal = $items->sum(fn($item) => (float)$item['price_snapshot'] * (int)$item['quantity']);
        
        $shipping = (float) $get('shipping_cost') ?? 0;
        $vat = (float) $get('vat_tax') ?? 0;
        $discount = (float) $get('discount_amount') ?? 0;

        $total = max(0, $subtotal + $shipping + $vat - $discount);
        $set('total_price', number_format($total, 2, '.', ''));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Order #')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.email')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Medium),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed'  => 'success',
                        'processing' => 'warning',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'    => 'success',
                        'failed'  => 'danger',
                        default   => 'warning',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'processing' => 'Processing',
                        'completed'  => 'Completed',
                        'cancelled'  => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment')
                    ->options([
                        'pending' => 'Pending',
                        'paid'    => 'Paid',
                        'failed'  => 'Failed',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->tooltip('Edit'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
