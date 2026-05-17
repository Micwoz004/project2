<?php

namespace App\Domain\Voting\Services\Sms;

use DomainException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpSmsProvider implements SmsProvider
{
    public function send(string $phone, string $message): void
    {
        $url = (string) config('services.sms.url');

        if (trim($url) === '') {
            Log::warning('sms.provider.http.rejected_missing_url');

            throw new DomainException('Brak konfiguracji bramki SMS.');
        }

        Log::info('sms.provider.http.send.start', [
            'phone_hash' => hash('sha256', $phone),
            'message_length' => mb_strlen($message),
        ]);

        $response = $this->request()->post($url, array_filter([
            'to' => $phone,
            'message' => $message,
            'from' => config('services.sms.from'),
        ]));

        if (! $response->successful()) {
            Log::warning('sms.provider.http.send.rejected', [
                'phone_hash' => hash('sha256', $phone),
                'status' => $response->status(),
            ]);

            throw new DomainException('Bramka SMS odrzuciła wiadomość.');
        }

        Log::info('sms.provider.http.send.success', [
            'phone_hash' => hash('sha256', $phone),
            'status' => $response->status(),
        ]);
    }

    private function request(): PendingRequest
    {
        $request = Http::asJson()->timeout((int) config('services.sms.timeout', 10));
        $token = (string) config('services.sms.token');

        if (trim($token) === '') {
            return $request;
        }

        return $request->withToken($token);
    }
}
