<?php

namespace App\Console\Commands;

use App\Domain\LegacyImport\Services\LegacyFixtureImportService;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

class ImportLegacyFixtureCommand extends Command
{
    protected $signature = 'sbo:legacy-import
        {path : Path to normalized legacy JSON payload}
        {--source= : Optional source label stored in legacy_import_batches}';

    protected $description = 'Import normalized SBO legacy JSON data into the new domain schema.';

    public function handle(LegacyFixtureImportService $importService): int
    {
        $path = (string) $this->argument('path');
        $source = (string) ($this->option('source') ?: $path);

        Log::info('legacy_import.command.start', [
            'path' => $path,
            'source' => $source,
        ]);

        if (! is_file($path) || ! is_readable($path)) {
            Log::warning('legacy_import.command.unreadable_file', [
                'path' => $path,
            ]);
            $this->error('Legacy import file is not readable.');

            return self::FAILURE;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            Log::warning('legacy_import.command.read_failed', [
                'path' => $path,
            ]);
            $this->error('Legacy import file could not be read.');

            return self::FAILURE;
        }

        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::warning('legacy_import.command.invalid_json', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);
            $this->error('Legacy import file is not valid JSON.');

            return self::FAILURE;
        }

        if (! is_array($payload)) {
            Log::warning('legacy_import.command.invalid_payload_shape', [
                'path' => $path,
            ]);
            $this->error('Legacy import payload must be a JSON object.');

            return self::FAILURE;
        }

        try {
            $batch = $importService->import($payload, $source);
        } catch (DomainException $exception) {
            Log::warning('legacy_import.command.rejected', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            Log::error('legacy_import.command.failed', [
                'path' => $path,
                'exception' => $exception,
            ]);
            $this->error('Legacy import failed unexpectedly.');

            return self::FAILURE;
        }

        Log::info('legacy_import.command.success', [
            'path' => $path,
            'batch_id' => $batch->id,
            'source' => $batch->source_path,
        ]);

        $this->info("Legacy import batch {$batch->id} completed.");

        return self::SUCCESS;
    }
}
