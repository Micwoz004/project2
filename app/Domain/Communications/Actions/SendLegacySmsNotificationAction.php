<?php

namespace App\Domain\Communications\Actions;

use App\Domain\Communications\Enums\LegacyCommunicationTrigger;
use App\Domain\Voting\Models\SmsLog;
use App\Domain\Voting\Services\Sms\SmsProvider;
use DomainException;
use Illuminate\Support\Facades\Log;

class SendLegacySmsNotificationAction
{
    public function __construct(
        private readonly SmsProvider $smsProvider,
    ) {}

    public function execute(LegacyCommunicationTrigger $trigger, string $phone, string $message, ?string $ip = null): SmsLog
    {
        Log::info('legacy_sms_notification.send.start', [
            'trigger' => $trigger->value,
        ]);

        if ($trigger->channel() !== 'sms') {
            Log::warning('legacy_sms_notification.send.rejected_channel', [
                'trigger' => $trigger->value,
                'channel' => $trigger->channel(),
            ]);

            throw new DomainException('Trigger legacy nie jest kanałem SMS.');
        }

        $normalizedPhone = trim($phone);
        $normalizedMessage = trim($message);

        if ($normalizedPhone === '') {
            Log::warning('legacy_sms_notification.send.rejected_missing_phone', [
                'trigger' => $trigger->value,
            ]);

            throw new DomainException('Numer telefonu odbiorcy SMS jest wymagany.');
        }

        if ($normalizedMessage === '') {
            Log::warning('legacy_sms_notification.send.rejected_missing_message', [
                'trigger' => $trigger->value,
            ]);

            throw new DomainException('Treść SMS jest wymagana.');
        }

        try {
            $this->smsProvider->send($normalizedPhone, $normalizedMessage);
        } catch (DomainException $exception) {
            Log::warning('legacy_sms_notification.send.rejected_provider', [
                'trigger' => $trigger->value,
                'reason' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $smsLog = SmsLog::query()->create([
            'phone' => $normalizedPhone,
            'ip' => $ip,
        ]);

        Log::info('legacy_sms_notification.send.success', [
            'trigger' => $trigger->value,
            'sms_log_id' => $smsLog->id,
        ]);

        return $smsLog;
    }
}
