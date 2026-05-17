<?php

use App\Domain\Voting\Services\Sms\SmsConfigurationValidator;

it('accepts local null sms provider configuration', function (): void {
    config([
        'services.sms.driver' => 'null',
        'services.sms.voting_token_message' => 'Kod SMS do głosowania SBO: {activationSmsToken}',
    ]);

    expect(app(SmsConfigurationValidator::class)->validate(false))->toBe([]);

    $this->artisan('sbo:sms-config-check')->assertSuccessful();
});

it('rejects production configuration without a real sms provider', function (): void {
    config([
        'services.sms.driver' => 'null',
        'services.sms.voting_token_message' => 'Kod SMS do głosowania SBO: {activationSmsToken}',
    ]);

    $issues = app(SmsConfigurationValidator::class)->validate(true);

    expect($issues)->toContain([
        'code' => 'sms_driver_not_http',
        'message' => 'Na produkcji SMS_DRIVER musi wskazywać realny provider HTTP.',
    ]);

    $this->artisan('sbo:sms-config-check', [
        '--production' => true,
    ])->assertFailed();
});

it('requires token placeholder in sms voting message', function (): void {
    config([
        'services.sms.driver' => 'http',
        'services.sms.url' => 'https://sms.example.test/send',
        'services.sms.token' => 'secret',
        'services.sms.from' => 'SBO',
        'services.sms.timeout' => 10,
        'services.sms.voting_token_message' => 'Kod SMS do głosowania SBO.',
    ]);

    $issues = app(SmsConfigurationValidator::class)->validate(true);

    expect($issues)->toContain([
        'code' => 'sms_voting_token_placeholder_missing',
        'message' => 'SMS_VOTING_TOKEN_MESSAGE musi zawierać {activationSmsToken}.',
    ]);
});

it('accepts complete production http sms configuration', function (): void {
    config([
        'services.sms.driver' => 'http',
        'services.sms.url' => 'https://sms.example.test/send',
        'services.sms.token' => 'secret',
        'services.sms.from' => 'SBO',
        'services.sms.timeout' => 10,
        'services.sms.voting_token_message' => 'Kod SMS do głosowania SBO: {activationSmsToken}',
    ]);

    expect(app(SmsConfigurationValidator::class)->validate(true))->toBe([]);

    $this->artisan('sbo:sms-config-check', [
        '--production' => true,
        '--json' => true,
    ])->assertSuccessful();
});
