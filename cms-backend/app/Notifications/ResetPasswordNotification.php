<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token) {}

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

        $intro = $notifiable->is_legacy
            ? 'Your account was migrated from our previous platform. For security, we need you to set a fresh password before signing in.'
            : 'You are receiving this email because we received a password reset request for your account.';

        return (new MailMessage)
            ->subject('Reset your 99accs password')
            ->greeting('Hello ' . ($notifiable->first_name ?: 'there') . ',')
            ->line($intro)
            ->action('Set a new password', $url)
            ->line('This link will expire in ' . config('auth.passwords.customers.expire', 60) . ' minutes.')
            ->line('If you did not request a password reset, no further action is required — your account is safe.');
    }
}
