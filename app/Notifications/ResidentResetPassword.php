<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResidentResetPassword extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return $this->buildResidentMailMessage($this->resetUrl($notifiable), $notifiable);
    }

    private function buildResidentMailMessage(string $url, mixed $notifiable): MailMessage
    {
        $expiresInMinutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
        $data = [
            'resetPasswordUrl' => $url,
            'residentEmail' => $notifiable->getEmailForPasswordReset(),
            'cityName' => $this->cityName(),
            'expiresInMinutes' => $expiresInMinutes,
            'supportEmail' => $this->supportEmail(),
            'supportPhone' => $this->supportPhone(),
            'accessibilityUrl' => url('/informacje/deklaracja-dostepnosci'),
            'privacyUrl' => url('/informacje/prywatnosc'),
        ];

        return (new MailMessage)
            ->subject('Ustaw nowe hasło do konta mieszkańca')
            ->view('mail.resident-password-reset', $data)
            ->text('mail.resident-password-reset-text', $data);
    }

    private function cityName(): string
    {
        return config('app.name') === 'Laravel' ? 'Miasta' : (string) config('app.name');
    }

    private function supportEmail(): string
    {
        return (string) config('mail.from.address', 'kontakt@example.test');
    }

    private function supportPhone(): string
    {
        return (string) config('services.public_support.phone', '+48 000 000 000');
    }
}
