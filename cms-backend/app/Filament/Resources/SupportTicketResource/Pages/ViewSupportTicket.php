<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    /** Bound to the inline chat composer rendered in the conversation infolist. */
    public ?string $replyBody = '';

    protected function getHeaderActions(): array
    {
        return [
            $this->closeAction(),
            $this->reopenAction(),
        ];
    }

    /**
     * Inline staff reply (WhatsApp-style composer). Appends a message authored
     * by the admin, (re)opens the thread, emails the customer, and refreshes.
     */
    public function sendReply(): void
    {
        $body = trim((string) $this->replyBody);
        if ($body === '') {
            return;
        }

        /** @var SupportTicket $ticket */
        $ticket = $this->getRecord();

        $message = new SupportTicketMessage([
            'body'       => Str::limit($body, 5000, ''),
            'is_opening' => false,
        ]);
        $message->ticket()->associate($ticket);
        $message->author()->associate(auth()->user()); // admin User
        $message->save();

        // Staff activity (re)opens the thread and stamps the reply time.
        $ticket->status = SupportTicket::STATUS_OPEN;
        $ticket->last_reply_at = now();
        $ticket->save();

        // Email the customer (queued under database/redis, inline on sync).
        $ticket->customer?->sendTicketReplyNotification($ticket, Str::limit($body, 120), false);

        $this->replyBody = '';
        $this->refreshThread();
        $this->dispatch('chat-updated'); // tells the view to scroll to the newest message

        Notification::make()
            ->title('Reply sent to customer')
            ->success()
            ->send();
    }

    private function closeAction(): Action
    {
        return Action::make('close')
            ->label('Close ticket')
            ->icon('heroicon-o-check-circle')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn () => $this->getRecord()->status !== SupportTicket::STATUS_CLOSED)
            ->action(function (): void {
                $this->getRecord()->close();
                Notification::make()->title('Ticket closed')->success()->send();
                $this->refreshThread();
            });
    }

    private function reopenAction(): Action
    {
        return Action::make('reopen')
            ->label('Reopen ticket')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn () => $this->getRecord()->status === SupportTicket::STATUS_CLOSED)
            ->action(function (): void {
                $ticket = $this->getRecord();
                $ticket->status = SupportTicket::STATUS_OPEN;
                $ticket->save();
                Notification::make()->title('Ticket reopened')->success()->send();
                $this->refreshThread();
            });
    }

    /** Reload the record + its thread so the infolist reflects the new message. */
    private function refreshThread(): void
    {
        $this->record->unsetRelation('messages');
        $this->record->refresh();
    }
}
