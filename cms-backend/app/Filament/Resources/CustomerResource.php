<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Password;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Customers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profile')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('first_name')->required()->maxLength(255),
                        Forms\Components\TextInput::make('last_name')->required()->maxLength(255),
                        Forms\Components\TextInput::make('email')->email()->required()->maxLength(255),
                        Forms\Components\TextInput::make('phone')->tel()->maxLength(255),
                    ]),

                Forms\Components\Section::make('Security')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->maxLength(255)
                            ->dehydrated(fn ($state) => filled($state))
                            ->helperText('Leave blank to keep the current password.'),
                        Forms\Components\Toggle::make('must_reset_password')
                            ->label('Force password reset on next login')
                            ->helperText('User cannot sign in until they complete the email-based reset flow.'),
                        Forms\Components\Toggle::make('is_blocked')
                            ->label('Block account')
                            ->helperText('Blocks login and revokes existing tokens (run "Block" action to revoke now).'),
                        Forms\Components\Toggle::make('is_legacy')
                            ->label('Migrated from previous platform')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Set automatically by the customers:import command.'),
                    ]),

                Forms\Components\Section::make('Activity & Legacy Metadata')
                    ->description('Read-only — populated by the WordPress import or sign-in events.')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('legacy_id')
                            ->label('WordPress User ID')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('—'),
                        Forms\Components\DateTimePicker::make('migrated_at')
                            ->label('Migrated at')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('total_spent')
                            ->label('Total spent (legacy)')
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('0.00')
                            ->helperText('Lifetime spend imported from WooCommerce.'),
                        Forms\Components\TextInput::make('legacy_orders_count')
                            ->label('Orders count (legacy)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('0')
                            ->helperText('Order count imported from WooCommerce — does not include new orders placed here.'),
                        Forms\Components\DateTimePicker::make('last_login_at')
                            ->label('Last login')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Never'),
                        Forms\Components\TextInput::make('last_login_ip')
                            ->label('Last login IP')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('—'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('Name')
                    ->getStateUsing(fn ($record) => trim("{$record->first_name} {$record->last_name}"))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Medium),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()->sortable()->copyable()->copyMessage('Email copied'),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()->placeholder('—'),

                Tables\Columns\IconColumn::make('is_legacy')
                    ->label('Legacy')
                    ->boolean()
                    ->trueIcon('heroicon-o-archive-box')
                    ->falseIcon('heroicon-o-minus-small')
                    ->tooltip(fn ($record) => $record->is_legacy ? 'Migrated from previous platform' : ''),

                Tables\Columns\IconColumn::make('must_reset_password')
                    ->label('Reset')
                    ->boolean()
                    ->trueIcon('heroicon-o-key')
                    ->falseIcon('heroicon-o-minus-small')
                    ->color(fn ($record) => $record->must_reset_password ? 'warning' : 'gray')
                    ->tooltip(fn ($record) => $record->must_reset_password ? 'Must reset password to sign in' : ''),

                Tables\Columns\IconColumn::make('is_blocked')
                    ->label('Blocked')
                    ->boolean()
                    ->trueIcon('heroicon-o-no-symbol')
                    ->falseIcon('heroicon-o-minus-small')
                    ->color(fn ($record) => $record->is_blocked ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last login')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->placeholder('Never'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_legacy')->label('Legacy users'),
                TernaryFilter::make('must_reset_password')->label('Pending password reset'),
                TernaryFilter::make('is_blocked')->label('Blocked accounts'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()->icon('heroicon-o-pencil-square')->color('primary')->tooltip('Edit'),

                Tables\Actions\Action::make('forceReset')
                    ->iconButton()
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->tooltip('Force password reset (sends email)')
                    ->requiresConfirmation()
                    ->modalHeading('Force password reset?')
                    ->modalDescription(fn (Customer $record) => "We'll flip the must_reset_password flag on {$record->email} and email them a reset link. They cannot sign in until they complete the flow.")
                    ->action(function (Customer $record) {
                        $record->forceFill(['must_reset_password' => true])->save();
                        $record->tokens()->delete();
                        Password::broker('customers')->sendResetLink(['email' => $record->email]);

                        Notification::make()
                            ->title('Reset email sent')
                            ->body("Reset link emailed to {$record->email}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('toggleBlock')
                    ->iconButton()
                    ->icon(fn (Customer $record) => $record->is_blocked ? 'heroicon-o-lock-open' : 'heroicon-o-no-symbol')
                    ->color(fn (Customer $record) => $record->is_blocked ? 'success' : 'danger')
                    ->tooltip(fn (Customer $record) => $record->is_blocked ? 'Unblock account' : 'Block account')
                    ->requiresConfirmation()
                    ->action(function (Customer $record) {
                        $blocking = ! $record->is_blocked;
                        $record->forceFill(['is_blocked' => $blocking])->save();
                        if ($blocking) {
                            $record->tokens()->delete();
                        }

                        Notification::make()
                            ->title($blocking ? 'Account blocked' : 'Account unblocked')
                            ->success()
                            ->send();
                    }),
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
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
