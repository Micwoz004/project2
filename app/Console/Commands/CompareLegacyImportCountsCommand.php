<?php

namespace App\Console\Commands;

use App\Domain\LegacyImport\Services\LegacyImportCountComparator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

class CompareLegacyImportCountsCommand extends Command
{
    protected $signature = 'sbo:legacy-import-counts
        {--connection=legacy_mysql : Laravel DB connection pointing at imported legacy MySQL dump}
        {--fail-on-mismatch : Return a failure code when comparable target counts differ}
        {--json : Print the comparison result as JSON}';

    protected $description = 'Compare legacy staging table counts with migrated target table counts.';

    public function handle(LegacyImportCountComparator $comparator): int
    {
        $connection = (string) $this->option('connection');
        $failOnMismatch = (bool) $this->option('fail-on-mismatch');
        $json = (bool) $this->option('json');

        Log::info('legacy_import_counts.command.start', [
            'connection' => $connection,
            'fail_on_mismatch' => $failOnMismatch,
            'json' => $json,
        ]);

        try {
            $result = $comparator->compare($connection);
        } catch (Throwable $exception) {
            Log::error('legacy_import_counts.command.failed', [
                'connection' => $connection,
                'exception' => $exception,
            ]);
            $this->error('Legacy import count comparison failed unexpectedly.');

            return self::FAILURE;
        }

        if ($json) {
            $this->printJson($result);
        } else {
            $this->printTable($result);
        }

        $hasBlockingDifference = $result['summary']['mismatched'] > 0 || $result['summary']['missing_target'] > 0;

        if ($hasBlockingDifference) {
            Log::warning('legacy_import_counts.command.differences_found', [
                'connection' => $connection,
                'mismatched_count' => $result['summary']['mismatched'],
                'missing_target_count' => $result['summary']['missing_target'],
            ]);
        }

        if ($failOnMismatch && $hasBlockingDifference) {
            $this->error('Legacy import count comparison found mismatches.');

            return self::FAILURE;
        }

        Log::info('legacy_import_counts.command.success', [
            'connection' => $connection,
            'matched_count' => $result['summary']['matched'],
            'mismatched_count' => $result['summary']['mismatched'],
            'missing_source_count' => $result['summary']['missing_source'],
            'missing_target_count' => $result['summary']['missing_target'],
        ]);

        return self::SUCCESS;
    }

    /**
     * @param  array{summary: array<string, int>, rows: list<array<string, mixed>>}  $result
     */
    private function printTable(array $result): void
    {
        $this->table(
            ['legacy_table', 'target_table', 'source_count', 'target_count', 'difference', 'status'],
            array_map(
                static fn (array $row): array => [
                    $row['legacy_table'],
                    $row['target_table'] ?? '-',
                    $row['source_count'] ?? '-',
                    $row['target_count'] ?? '-',
                    $row['difference'] ?? '-',
                    $row['status'],
                ],
                $result['rows'],
            ),
        );

        $this->info(sprintf(
            'Matched: %d, mismatched: %d, missing source: %d, missing target: %d, skipped: %d.',
            $result['summary']['matched'],
            $result['summary']['mismatched'],
            $result['summary']['missing_source'],
            $result['summary']['missing_target'],
            $result['summary']['skipped'],
        ));
    }

    /**
     * @param  array{summary: array<string, int>, rows: list<array<string, mixed>>}  $result
     */
    private function printJson(array $result): void
    {
        try {
            $this->line(json_encode($result, JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            Log::error('legacy_import_counts.command.json_failed', [
                'exception' => $exception,
            ]);
            $this->error('Legacy import count comparison could not be encoded as JSON.');
        }
    }
}
