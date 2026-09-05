<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyPendingEmail extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $pendingEmail,
        public readonly int $requestedAt,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifikasi email baru Anda')
            ->greeting('Halo!')
            ->line('Kami menerima permintaan untuk menggunakan alamat email ini pada akun NADI Anda.')
            ->action('Verifikasi email baru', $this->verificationUrl())
            ->line('Tautan ini berlaku selama 60 menit. Abaikan email ini jika Anda tidak meminta perubahan.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'pending_email' => $this->pendingEmail,
        ];
    }

    public function verificationUrl(): string
    {
        return URL::temporarySignedRoute(
            'account.email.verify',
            now()->addMinutes(60),
            [
                'user' => $this->userId,
                'hash' => hash('sha256', $this->pendingEmail),
                'requested' => $this->requestedAt,
            ],
        );
    }
}
