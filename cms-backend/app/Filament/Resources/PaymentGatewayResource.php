<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentGatewayResource\Pages;
use App\Models\PaymentGateway;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Admin UI for payment gateways. Gateways themselves are seeded — admins only
 * edit existing rows (fill in credentials, toggle active/test mode). Creation
 * and deletion are disabled because adding a new gateway requires a matching
 * provider class in code, not just a DB row.
 *
 * The "Credentials" section renders different fields per gateway slug so the
 * admin sees only the keys the chosen provider actually understands.
 */
class PaymentGatewayResource extends Resource
{
    protected static ?string $model = PaymentGateway::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Provider identifier — bound to a driver class in code, cannot be changed.')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('logo')
                            ->image()
                            ->directory('payment-gateways')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower values appear first on the checkout page.'),
                        Forms\Components\Group::make([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->helperText('Show this gateway on the checkout page.'),
                            Forms\Components\Toggle::make('is_test_mode')
                                ->label('Test mode')
                                ->helperText('When on, the provider should use sandbox endpoints / test keys.'),
                        ]),
                    ])->columns(2),

                Forms\Components\Section::make('Stripe credentials')
                    ->description('Credentials are encrypted at rest. Find these in your Stripe dashboard → Developers → API keys.')
                    ->schema([
                        Forms\Components\TextInput::make('credentials.publishable_key')
                            ->label('Publishable key')
                            ->helperText('Starts with pk_test_ or pk_live_')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('credentials.secret_key')
                            ->label('Secret key')
                            ->password()
                            ->revealable()
                            ->helperText('Starts with sk_test_ or sk_live_')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('credentials.webhook_secret')
                            ->label('Webhook signing secret')
                            ->password()
                            ->revealable()
                            ->helperText('Starts with whsec_ — from Stripe → Developers → Webhooks → your endpoint.')
                            ->columnSpanFull()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->visible(fn (?PaymentGateway $record) => $record?->slug === 'stripe'),

                Forms\Components\Section::make('Cryptomus credentials')
                    ->description('Credentials are encrypted at rest. Find these in your Cryptomus dashboard → Personal → API.')
                    ->schema([
                        Forms\Components\TextInput::make('credentials.merchant_id')
                            ->label('Merchant ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('credentials.payment_key')
                            ->label('Payment API key')
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('config.callback_url')
                            ->label('Webhook callback URL')
                            ->helperText('Leave blank to use {APP_URL}/api/v1/webhooks/cryptomus by default.')
                            ->columnSpanFull()
                            ->url()
                            ->maxLength(500),
                    ])
                    ->columns(2)
                    ->visible(fn (?PaymentGateway $record) => $record?->slug === 'crypto'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('credentials_set')
                    ->label('Configured')
                    ->boolean()
                    ->state(fn (PaymentGateway $record) => self::hasRequiredCredentials($record))
                    ->tooltip(fn (PaymentGateway $record) => self::hasRequiredCredentials($record)
                        ? 'All required keys are filled in'
                        : 'Missing one or more required credentials'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->disabled(fn (PaymentGateway $record) => ! self::hasRequiredCredentials($record))
                    ->tooltip(fn (PaymentGateway $record) => ! self::hasRequiredCredentials($record)
                        ? 'Fill in credentials before activating'
                        : null),
                Tables\Columns\IconColumn::make('is_test_mode')
                    ->label('Test')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->tooltip('Edit'),
            ])
            ->bulkActions([
                // No bulk delete — gateways are bound to code, deleting a row leaves an orphan driver
            ]);
    }

    public static function canCreate(): bool
    {
        // Gateways come from the seeder — adding new ones requires a provider class
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentGateways::route('/'),
            'edit'  => Pages\EditPaymentGateway::route('/{record}/edit'),
        ];
    }

    private static function hasRequiredCredentials(PaymentGateway $gateway): bool
    {
        return match ($gateway->slug) {
            'stripe' => filled($gateway->credential('secret_key')),
            'crypto' => filled($gateway->credential('merchant_id')) && filled($gateway->credential('payment_key')),
            default  => false,
        };
    }
}
