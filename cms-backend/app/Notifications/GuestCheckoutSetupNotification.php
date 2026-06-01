<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent right after a guest checks out and we auto-create their account. Distinct
 * from ResetPasswordNotification so the copy can lead with "thanks for your
 * order" instead of "your account was migrated" — same reset-token mechanism
 * underneath.
 */
class GuestCheckoutSetupNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
        public readonly ?Order $order = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontend = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');
        $url = sprintf(
            '%s/reset-password?token=%s&email=%s',
            $frontend,
            $this->token,
            urlencode($notifiable->getEmailForPasswordReset()),
        );

        $message = (new MailMessage)
            ->subject('Finish setting up your 99accs account')
            ->greeting('Hello ' . ($notifiable->first_name ?: 'there') . ',')
            ->line('Thanks for your order! We have created a 99accs account for you so you can track your purchase and any future orders.');

        if ($this->order) {
            $message->line("Your order number is **{$this->order->number}**.");
        }

        return $message
            ->line('Set a password to access your account and view this order at any time:')
            ->action('Set my password', $url)
            ->line('This link will expire in ' . config('auth.passwords.customers.expire', 60) . ' minutes. You can request a new one any time from the login page.')
            ->line('If you did not place this order, please contact our support team right away.');
    }
}
