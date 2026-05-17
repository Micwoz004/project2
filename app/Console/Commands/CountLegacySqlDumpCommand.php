<?php

namespace App\Console\Commands;

use App\Domain\LegacyImport\Services\LegacyImportCountComparator;
use App\Domain\LegacyImport\Services\LegacySqlDumpTableCounter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
use Throwable;

class CountLegacySqlDumpCommand extends Command
{
    protected $signature = 'sbo:legacy-dump-counts
        {path : Path to raw legacy MySQL .sql dump}
        {--compare-target : Compare parsed dump counts with migrated target tables}
        {--fail-on-mismatch : Return a failure code when comparable target counts differ}
        {--json : Print result as JSON}';

    protected $description = 'Count INSERT rows in a raw legacy MySQL dump without staging the dump in MySQL.';

    public function handle(
        LegacySqlDumpTableCounter $counter,
        LegacyImportCountComparator $comparator,
    ): int {
        $path = (string) $this->argument('path');
        $compareTarget = (bool) $this->option('compare-target');
        $failOnMismatch = (bool) $this->option('fail-on-mismatch');
        $json = (bool) $this->option('json');

        Log::info('legacy_sql_dump_counts.command.start', [
            'path' => $path,
            'compare_target' => $compareTarget,
            'fail_on_mismatch' => $failOnMismatch,
            'json' => $json,
        ]);

        try {
            $counts = $counter->count($path);
            $result = $compareTarget
                ? $comparator->compareSourceCounts($counts, $path)
                : $this->rawCountResult($counts);
        } catch (RuntimeException $exception) {
            Log::warning('legacy_sql_dump_counts.command.rejected', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            Log::error('legacy_sql_dump_counts.command.failed', [
                'path' => $path,
                'exception' => $exception,
            ]);
            $this->error('Legacy SQL dump count failed unexpectedly.');

            return self::FAILURE;
        }

        if ($json) {
            $this->printJson($result);
        } elseif ($compareTarget) {
            $this->printComparisonTable($result);
        } else {
            $this->printCountsTable($result);
        }

        if ($compareTarget && $this->hasBlockingDifference($result)) {
            Log::warning('legacy_sql_dump_counts.command.differences_found', [
                'path' => $path,
                'mismatched_count' => $result['summary']['mismatched'],
                'missing_target_count' => $result['summary']['missing_target'],
            ]);
        }

        if ($compareTarget && $failOnMismatch && $this->hasBlockingDifference($result)) {
            $this->error('Legacy SQL dump count comparison found mismatches.');

            return self::FAILURE;
        }

        Log::info('legacy_sql_dump_counts.command.success', [
            'path' => $path,
            'tables_count' => $compareTarget ? $result['summary']['total'] : $result['summary']['tables'],
        ]);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, int>  $counts
     * @return array{summary: array<string, int>, tables: array<string, int>}
     */
    private function rawCountResult(array $counts): array
    {
        return [
            'summary' => [
                'tables' => count($counts),
                'rows' => array_sum($counts),
            ],
            'tables' => $counts,
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function printCountsTable(array $result): void
    {
        $rows = [];

        foreach ($result['tables'] as $table => $count) {
            $rows[] = [$table, $count];
        }

        $this->table(['legacy_table', 'source_count'], $rows);
        $this->info(sprintf(
            'Tables: %d, rows: %d.',
            $result['summary']['tables'],
            $result['summary']['rows'],
        ));
    }

    /**
     * @param  array{summary: array<string, int>, rows: list<array<string, mixed>>}  $result
     */
    private function printComparisonTable(array $result): void
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
     * @param  array<string, mixed>  $result
     */
    private function printJson(array $result): void
    {
        try {
            $this->line(json_encode($result, JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            Log::error('legacy_sql_dump_counts.command.json_failed', [
                'exception' => $exception,
            ]);
            $this->error('Legacy SQL dump count result could not be encoded as JSON.');
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function hasBlockingDifference(array $result): bool
    {
        return $result['summary']['mismatched'] > 0 || $result['summary']['missing_target'] > 0;
    }
}
