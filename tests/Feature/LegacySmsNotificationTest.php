<?php

use App\Domain\Communications\Actions\SendLegacySmsNotificationAction;
use App\Domain\Communications\Enums\LegacyCommunicationTrigger;
use App\Domain\Voting\Models\SmsLog;
use App\Domain\Voting\Services\Sms\SmsProvider;

class FakeLegacySmsProvider implements SmsProvider
{
    /**
     * @var list<array{phone: string, message: string}>
     */
    public array $messages = [];

    public function send(string $phone, string $message): void
    {
        $this->messages[] = [
            'phone' => $phone,
            'message' => $message,
        ];
    }
}

it('sends legacy sms notifications through configured provider without storing message content', function (): void {
    $provider = new FakeLegacySmsProvider;
    $this->app->instance(SmsProvider::class, $provider);

    $smsLog = app(SendLegacySmsNotificationAction::class)->execute(
        LegacyCommunicationTrigger::TaskCallToCorrectionSms,
        ' 500600700 ',
        'Prosimy o korektę projektu.',
        '127.0.0.1',
    );

    expect($provider->messages)->toBe([[
        'phone' => '500600700',
        'message' => 'Prosimy o korektę projektu.',
    ]])
        ->and($smsLog->phone)->toBe('500600700')
        ->and($smsLog->ip)->toBe('127.0.0.1')
        ->and(SmsLog::query()->count())->toBe(1);
});

it('rejects mail triggers in legacy sms notification action', function (): void {
    $provider = new FakeLegacySmsProvider;
    $this->app->instance(SmsProvider::class, $provider);

    app(SendLegacySmsNotificationAction::class)->execute(
        LegacyCommunicationTrigger::TaskSubmitted,
        '500600700',
        'Wiadomość',
    );
})->throws(DomainException::class, 'Trigger legacy nie jest kanałem SMS.');

it('rejects legacy sms notification without recipient phone or message', function (): void {
    $provider = new FakeLegacySmsProvider;
    $this->app->instance(SmsProvider::class, $provider);

    app(SendLegacySmsNotificationAction::class)->execute(
        LegacyCommunicationTrigger::TaskCallToCorrectionSms,
        '',
        'Wiadomość',
    );
})->throws(DomainException::class, 'Numer telefonu odbiorcy SMS jest wymagany.');
