<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordOtp extends Notification
{
    public function __construct(public readonly string $otp) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode OTP Reset Password NADI')
            ->greeting('Halo!')
            ->line('Gunakan kode berikut untuk mengganti password akun NADI Anda:')
            ->line('**'.$this->otp.'**')
            ->line('Kode ini berlaku selama 5 menit dan hanya dapat digunakan sekali.')
            ->line('Jangan bagikan kode ini kepada siapa pun, termasuk pihak yang mengaku sebagai tim NADI.')
            ->line('Jika Anda tidak meminta reset password, abaikan email ini. Password Anda tetap aman.')
            ->salutation('Tim NADI');
    }
}
