<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emailed to the customer when a staff member replies to (or closes) their
 * ticket. Queued so a slow SMTP send never blocks the admin's reply action —
 * requires QUEUE_CONNECTION=database (or redis) + a running `queue:work`.
 * Falls back to inline send under the default `sync` connection.
 */
class TicketReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly string $replyExcerpt,
        public readonly bool $wasClosed = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontend = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');
        $url = "{$frontend}/support/tickets/{$this->ticket->id}";

        $mail = (new MailMessage)
            ->subject("Re: {$this->ticket->subject} ({$this->ticket->ticket_number})")
            ->greeting('Hello ' . ($notifiable->first_name ?: 'there') . ',')
            ->line("Our support team has replied to your ticket {$this->ticket->ticket_number}.")
            ->line('"' . $this->replyExcerpt . '"')
            ->action('View the conversation', $url);

        if ($this->wasClosed) {
            $mail->line('This ticket has been marked as resolved. Reply any time to reopen it.');
        }

        return $mail->line('Thanks for choosing 99accs.');
    }
}
