<?php

namespace App\Domain\Voting\Services\Sms;

use Illuminate\Support\Facades\Log;

class NullSmsProvider implements SmsProvider
{
    public function send(string $phone, string $message): void
    {
        Log::info('sms.provider.null.skipped', [
            'phone_hash' => hash('sha256', $phone),
            'message_length' => mb_strlen($message),
        ]);
    }
}
