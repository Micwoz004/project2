<?php

namespace App\Console\Commands;

use App\Domain\Voting\Services\Sms\SmsConfigurationValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

class CheckSmsConfigurationCommand extends Command
{
    protected $signature = 'sbo:sms-config-check
        {--production : Enforce production SMS requirements}
        {--json : Print validation issues as JSON}';

    protected $description = 'Validate SMS provider configuration for public voting tokens.';

    public function handle(SmsConfigurationValidator $validator): int
    {
        $production = (bool) $this->option('production');
        $json = (bool) $this->option('json');

        Log::info('sms.configuration.command.start', [
            'production' => $production,
            'json' => $json,
        ]);

        try {
            $issues = $validator->validate($production);
        } catch (Throwable $exception) {
            Log::error('sms.configuration.command.failed', [
                'exception' => $exception,
            ]);
            $this->error('SMS configuration validation failed unexpectedly.');

            return self::FAILURE;
        }

        if ($json) {
            $this->printJson($issues);
        } else {
            $this->printText($issues);
        }

        if ($issues !== []) {
            Log::warning('sms.configuration.command.rejected', [
                'production' => $production,
                'issues_count' => count($issues),
            ]);

            return self::FAILURE;
        }

        Log::info('sms.configuration.command.success', [
            'production' => $production,
        ]);

        return self::SUCCESS;
    }

    /**
     * @param  list<array{code: string, message: string}>  $issues
     */
    private function printText(array $issues): void
    {
        if ($issues === []) {
            $this->info('SMS configuration is valid.');

            return;
        }

        foreach ($issues as $issue) {
            $this->warn($issue['code'].': '.$issue['message']);
        }
    }

    /**
     * @param  list<array{code: string, message: string}>  $issues
     */
    private function printJson(array $issues): void
    {
        try {
            $this->line(json_encode(['issues' => $issues], JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            Log::error('sms.configuration.command.json_failed', [
                'exception' => $exception,
            ]);
            $this->error('SMS configuration issues could not be encoded as JSON.');
        }
    }
}
