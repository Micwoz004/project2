<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class ResidentEmailVerification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        return $this->buildResidentMailMessage($this->verificationUrl($notifiable), $notifiable);
    }

    private function buildResidentMailMessage(string $url, mixed $notifiable): MailMessage
    {
        $data = [
            'verificationUrl' => $url,
            'residentName' => $notifiable->name,
            'residentEmail' => $notifiable->getEmailForVerification(),
            'cityName' => $this->cityName(),
            'supportEmail' => $this->supportEmail(),
            'supportPhone' => $this->supportPhone(),
            'accessibilityUrl' => url('/informacje/deklaracja-dostepnosci'),
            'privacyUrl' => url('/informacje/prywatnosc'),
        ];

        return (new MailMessage)
            ->subject('Potwierdź adres e-mail w serwisie Budżet Obywatelski')
            ->view('mail.resident-email-verification', $data)
            ->text('mail.resident-email-verification-text', $data);
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
