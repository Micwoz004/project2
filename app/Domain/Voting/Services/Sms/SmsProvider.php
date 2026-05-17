<?php

namespace App\Domain\Voting\Services\Sms;

interface SmsProvider
{
    public function send(string $phone, string $message): void;
}
