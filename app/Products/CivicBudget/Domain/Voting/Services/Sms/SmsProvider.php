<?php

namespace App\Products\CivicBudget\Domain\Voting\Services\Sms;

interface SmsProvider
{
    public function send(string $phone, string $message): void;
}
