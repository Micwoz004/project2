<?php

namespace App\Domain\Voting\Services\Sms;

use Illuminate\Support\Facades\Log;

class SmsConfigurationValidator
{
    private const TOKEN_PLACEHOLDER = '{activationSmsToken}';

    private const SUMMARY_PLACEHOLDER = '{smsSummaryTable}';

    /**
     * @return list<array{code: string, message: string}>
     */
    public function validate(bool $production): array
    {
        $driver = (string) config('services.sms.driver', 'null');

        Log::info('sms.configuration.validate.start', [
            'driver' => $driver,
            'production' => $production,
        ]);

        $issues = [
            ...$this->driverIssues($driver, $production),
            ...$this->httpIssues($driver, $production),
            ...$this->messageIssues(),
        ];

        if ($issues !== []) {
            Log::warning('sms.configuration.validate.rejected', [
                'driver' => $driver,
                'production' => $production,
                'issues_count' => count($issues),
                'issue_codes' => array_column($issues, 'code'),
            ]);

            return $issues;
        }

        Log::info('sms.configuration.validate.success', [
            'driver' => $driver,
            'production' => $production,
        ]);

        return [];
    }

    /**
     * @return list<array{code: string, message: string}>
     */
    private function driverIssues(string $driver, bool $production): array
    {
        $issues = [];

        if (! in_array($driver, ['null', 'http'], true)) {
            $issues[] = [
                'code' => 'sms_driver_unknown',
                'message' => 'SMS_DRIVER musi mieć wartość null albo http.',
            ];
        }

        if ($production && $driver !== 'http') {
            $issues[] = [
                'code' => 'sms_driver_not_http',
                'message' => 'Na produkcji SMS_DRIVER musi wskazywać realny provider HTTP.',
            ];
        }

        return $issues;
    }

    /**
     * @return list<array{code: string, message: string}>
     */
    private function httpIssues(string $driver, bool $production): array
    {
        if ($driver !== 'http') {
            return [];
        }

        $issues = [];

        if (trim((string) config('services.sms.url')) === '') {
            $issues[] = [
                'code' => 'sms_api_url_missing',
                'message' => 'SMS_API_URL jest wymagane dla providera HTTP.',
            ];
        }

        if ($production && trim((string) config('services.sms.token')) === '') {
            $issues[] = [
                'code' => 'sms_api_token_missing',
                'message' => 'SMS_API_TOKEN jest wymagany przy produkcyjnej konfiguracji SMS.',
            ];
        }

        if ($production && trim((string) config('services.sms.from')) === '') {
            $issues[] = [
                'code' => 'sms_from_missing',
                'message' => 'SMS_FROM jest wymagane przy produkcyjnej konfiguracji SMS.',
            ];
        }

        if ((int) config('services.sms.timeout', 10) < 1) {
            $issues[] = [
                'code' => 'sms_timeout_invalid',
                'message' => 'SMS_TIMEOUT musi być dodatnią liczbą sekund.',
            ];
        }

        return $issues;
    }

    /**
     * @return list<array{code: string, message: string}>
     */
    private function messageIssues(): array
    {
        $tokenMessage = (string) config('services.sms.voting_token_message');
        $summaryMessage = (string) config('services.sms.voting_summary_message');

        $issues = [];

        if (trim($tokenMessage) === '') {
            $issues[] = [
                'code' => 'sms_voting_token_message_missing',
                'message' => 'SMS_VOTING_TOKEN_MESSAGE nie może być puste.',
            ];
        }

        if (! str_contains($tokenMessage, self::TOKEN_PLACEHOLDER)) {
            $issues[] = [
                'code' => 'sms_voting_token_placeholder_missing',
                'message' => 'SMS_VOTING_TOKEN_MESSAGE musi zawierać {activationSmsToken}.',
            ];
        }

        if (trim($summaryMessage) === '') {
            $issues[] = [
                'code' => 'sms_voting_summary_message_missing',
                'message' => 'SMS_VOTING_SUMMARY_MESSAGE nie może być puste.',
            ];
        }

        if (! str_contains($summaryMessage, self::SUMMARY_PLACEHOLDER)) {
            $issues[] = [
                'code' => 'sms_voting_summary_placeholder_missing',
                'message' => 'SMS_VOTING_SUMMARY_MESSAGE musi zawierać {smsSummaryTable}.',
            ];
        }

        return $issues;
    }
}
