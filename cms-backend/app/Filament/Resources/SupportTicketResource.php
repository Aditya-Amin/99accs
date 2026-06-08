<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationGroup = 'Support';

    protected static ?string $slug = 'support-tickets';

    protected static ?string $label = 'Support Ticket';

    protected static ?int $navigationSort = 1;

    /** Surface the open-ticket backlog as the nav badge. */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', SupportTicket::STATUS_NEW)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'openingMessage'])
            ->withCount(['messages as replies_count' => fn ($q) => $q->where('is_opening', false)]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('Ticket')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Customer')
                    ->description(fn (SupportTicket $record) => $record->customer?->email),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('game')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        SupportTicket::STATUS_NEW    => 'warning',
                        SupportTicket::STATUS_OPEN   => 'info',
                        SupportTicket::STATUS_CLOSED => 'gray',
                        default                      => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('replies_count')
                    ->label('Replies')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('last_reply_at')
                    ->label('Last reply')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        SupportTicket::STATUS_NEW    => 'New',
                        SupportTicket::STATUS_OPEN   => 'Open',
                        SupportTicket::STATUS_CLOSED => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('game')
                    ->options([
                        'valorant' => 'Valorant',
                        'fortnite' => 'Fortnite',
                        'legends'  => 'League of Legends',
                    ]),
            ])
            // Newest activity first; falls back to creation time for fresh tickets.
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->tooltip('Open conversation'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            // Two-column layout: chat on the left, ticket/customer details as a
            // sidebar on the right. Stacks to a single column on small screens.
            Infolists\Components\Grid::make(['default' => 1, 'lg' => 3])->schema([

                // ── Conversation (left, wider) ──────────────────────────────
                Infolists\Components\Section::make('Conversation')
                    ->columnSpan(['default' => 1, 'lg' => 2])
                    ->schema([
                        // Chat-style bubble thread + inline composer (see
                        // resources/views/filament/support-ticket-conversation.blade.php):
                        // customer left, staff/admin right. State = ordered messages.
                        Infolists\Components\ViewEntry::make('messages')
                            ->hiddenLabel()
                            ->view('filament.support-ticket-conversation'),
                    ]),

                // ── Ticket / customer details (right sidebar, stacked) ──────
                Infolists\Components\Section::make('Ticket')
                    ->columnSpan(['default' => 1, 'lg' => 1])
                    ->schema([
                        Infolists\Components\TextEntry::make('ticket_number')->label('Ticket'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                SupportTicket::STATUS_NEW    => 'warning',
                                SupportTicket::STATUS_OPEN   => 'info',
                                SupportTicket::STATUS_CLOSED => 'gray',
                                default                      => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('customer.full_name')->label('Customer'),
                        Infolists\Components\TextEntry::make('customer.email')
                            ->label('Email')
                            ->copyable()
                            ->icon('heroicon-m-envelope'),
                        Infolists\Components\TextEntry::make('subject'),
                        Infolists\Components\TextEntry::make('game')->badge(),
                        Infolists\Components\TextEntry::make('order_number')->placeholder('—'),
                        Infolists\Components\TextEntry::make('created_at')->dateTime(),
                        Infolists\Components\TextEntry::make('last_reply_at')->dateTime()->placeholder('—'),
                    ]),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'view'  => Pages\ViewSupportTicket::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        // Tickets originate from the storefront, never from the admin panel.
        return false;
    }
}
