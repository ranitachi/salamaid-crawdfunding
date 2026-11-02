<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminActionRequired extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private readonly array $data)
    {
    }

    /**
     * Database-only by default: this is safe for local development and does
     * not send email until an explicit mail policy is added.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('admin.notifications.email_enabled', false) && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->data;
    }

    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        $mail = (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject((string) ($this->data['title'] ?? 'Notifikasi Salamaid Admin'))
            ->line((string) ($this->data['message'] ?? 'Ada pekerjaan admin yang membutuhkan perhatian.'));

        if (filled($this->data['url'] ?? null)) {
            $mail->action('Buka di Admin', (string) $this->data['url']);
        }

        return $mail;
    }
}
