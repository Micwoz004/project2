<?php

namespace App\Domain\Dictionaries\Services;

use App\Domain\Dictionaries\Enums\DictionaryKind;
use App\Domain\Dictionaries\Models\DictionaryEntry;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LegacyDictionaryImportService
{
    private const TABLE_KIND_MAP = [
        'firstnamedictionary' => DictionaryKind::FirstName,
        'lastnamedictionary' => DictionaryKind::LastName,
        'motherlastnamedictionary' => DictionaryKind::MotherLastName,
    ];

    /**
     * @param  array<string, list<array<string, mixed>>>  $payload
     * @return array<string, int>
     */
    public function import(array $payload): array
    {
        Log::info('legacy_dictionary_import.start', [
            'tables_count' => count($payload),
        ]);

        return DB::transaction(function () use ($payload): array {
            $stats = [];

            foreach (self::TABLE_KIND_MAP as $sourceTable => $kind) {
                $stats[$sourceTable] = $this->importRows($sourceTable, $kind, $payload[$sourceTable] ?? []);
            }

            Log::info('legacy_dictionary_import.success', [
                'tables_count' => count($stats),
                'entries_count' => array_sum($stats),
            ]);

            return $stats;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importRows(string $sourceTable, DictionaryKind $kind, array $rows): int
    {
        foreach ($rows as $row) {
            $value = trim((string) Arr::get($row, 'value', Arr::get($row, 'name', '')));

            if ($value === '') {
                Log::warning('legacy_dictionary_import.skipped_empty_value', [
                    'source_table' => $sourceTable,
                    'legacy_id' => Arr::get($row, 'id'),
                ]);

                continue;
            }

            DictionaryEntry::query()->updateOrCreate([
                'source_table' => $sourceTable,
                'legacy_id' => (int) Arr::get($row, 'id'),
            ], [
                'kind' => $kind,
                'value' => mb_strtoupper($value),
                'active' => (bool) Arr::get($row, 'active', true),
            ]);
        }

        return count($rows);
    }
}
