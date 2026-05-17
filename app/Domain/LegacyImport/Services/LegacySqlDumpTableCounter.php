<?php

namespace App\Domain\LegacyImport\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use SplFileObject;

class LegacySqlDumpTableCounter
{
    /**
     * @return array<string, int>
     */
    public function count(string $path): array
    {
        Log::info('legacy_sql_dump.count.start', [
            'path' => $path,
        ]);

        if (! is_file($path) || ! is_readable($path)) {
            Log::warning('legacy_sql_dump.count.unreadable_file', [
                'path' => $path,
            ]);

            throw new RuntimeException('Legacy SQL dump file is not readable.');
        }

        $state = [
            'table' => null,
            'awaiting_values' => false,
            'in_string' => false,
            'quote' => null,
            'escape' => false,
            'paren_depth' => 0,
        ];
        $counts = [];
        $file = new SplFileObject($path);

        while (! $file->eof()) {
            $line = (string) $file->fgets();

            if ($state['table'] === null) {
                $this->rememberCreatedTable($line, $counts);
                $this->startInsert($line, $state, $counts);
            }

            if ($state['table'] !== null) {
                $this->consumeInsertLine($line, $state, $counts);
            }
        }

        ksort($counts);

        Log::info('legacy_sql_dump.count.success', [
            'path' => $path,
            'tables_count' => count($counts),
        ]);

        return $counts;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function rememberCreatedTable(string $line, array &$counts): void
    {
        if (preg_match('/^\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([A-Za-z0-9_]+)`?/i', $line, $matches) !== 1) {
            return;
        }

        $counts[$matches[1]] ??= 0;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, int>  $counts
     */
    private function startInsert(string $line, array &$state, array &$counts): void
    {
        if (preg_match('/^\s*INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?/i', $line, $matches) !== 1) {
            return;
        }

        $state['table'] = $matches[1];
        $state['awaiting_values'] = true;
        $counts[$matches[1]] ??= 0;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, int>  $counts
     */
    private function consumeInsertLine(string $line, array &$state, array &$counts): void
    {
        $offset = 0;

        if ($state['awaiting_values']) {
            $valuesPosition = stripos($line, 'VALUES');

            if ($valuesPosition === false) {
                return;
            }

            $offset = $valuesPosition + strlen('VALUES');
            $state['awaiting_values'] = false;
        }

        $length = strlen($line);

        for ($index = $offset; $index < $length; $index++) {
            $char = $line[$index];

            if ($state['in_string']) {
                $this->consumeStringCharacter($char, $state);

                continue;
            }

            if ($char === '\'' || $char === '"') {
                $state['in_string'] = true;
                $state['quote'] = $char;
                $state['escape'] = false;

                continue;
            }

            if ($char === '(') {
                if ($state['paren_depth'] === 0 && is_string($state['table'])) {
                    $counts[$state['table']]++;
                }

                $state['paren_depth']++;

                continue;
            }

            if ($char === ')') {
                $state['paren_depth'] = max(0, $state['paren_depth'] - 1);

                continue;
            }

            if ($char === ';' && $state['paren_depth'] === 0) {
                $this->resetInsertState($state);

                return;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function consumeStringCharacter(string $char, array &$state): void
    {
        if ($state['escape']) {
            $state['escape'] = false;

            return;
        }

        if ($char === '\\') {
            $state['escape'] = true;

            return;
        }

        if ($char === $state['quote']) {
            $state['in_string'] = false;
            $state['quote'] = null;
        }
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function resetInsertState(array &$state): void
    {
        $state['table'] = null;
        $state['awaiting_values'] = false;
        $state['in_string'] = false;
        $state['quote'] = null;
        $state['escape'] = false;
        $state['paren_depth'] = 0;
    }
}
