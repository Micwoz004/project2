<?php

namespace App\Products\EkoUslugi\Domain\Schedule\Actions;

use App\Products\EkoUslugi\Domain\Address\Models\EkoZone;
use App\Products\EkoUslugi\Domain\Schedule\Models\CollectionSchedule;
use App\Products\EkoUslugi\Domain\Schedule\Models\CollectionScheduleDate;
use App\Products\EkoUslugi\Domain\Waste\Models\WasteFraction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use SplFileObject;

class ImportCollectionScheduleCsvAction
{
    /**
     * @return array{schedule_id:int, imported:int, skipped:int}
     */
    public function execute(string $path, string $scheduleName, bool $replaceExisting = false): array
    {
        Log::info('eko_uslugi.schedule_import.start', [
            'path' => basename($path),
            'schedule_name' => $scheduleName,
            'replace_existing' => $replaceExisting,
        ]);

        if (! is_readable($path)) {
            Log::warning('eko_uslugi.schedule_import.rejected_unreadable_file', [
                'path' => basename($path),
            ]);

            throw new InvalidArgumentException('Plik importu harmonogramu jest niedostępny.');
        }

        return DB::transaction(function () use ($path, $scheduleName, $replaceExisting): array {
            $schedule = CollectionSchedule::query()->firstOrCreate(
                ['name' => $scheduleName],
                ['status' => 'active'],
            );

            if ($replaceExisting) {
                CollectionScheduleDate::query()
                    ->where('eko_collection_schedule_id', $schedule->id)
                    ->delete();
            }

            $stats = $this->importRows($path, $schedule);

            Log::info('eko_uslugi.schedule_import.success', [
                'schedule_id' => $schedule->id,
                'imported' => $stats['imported'],
                'skipped' => $stats['skipped'],
            ]);

            return [
                'schedule_id' => $schedule->id,
                'imported' => $stats['imported'],
                'skipped' => $stats['skipped'],
            ];
        });
    }

    /**
     * @return array{imported:int, skipped:int}
     */
    private function importRows(string $path, CollectionSchedule $schedule): array
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $file->setCsvControl($this->detectDelimiter($path));

        $headers = [];
        $imported = 0;
        $skipped = 0;

        foreach ($file as $lineNumber => $row) {
            if (! is_array($row) || $this->isBlankRow($row)) {
                continue;
            }

            if ($headers === []) {
                $headers = $this->normalizeHeaders($row);

                continue;
            }

            $payload = $this->rowPayload($headers, $row);
            $zone = $this->resolveZone($payload);
            $fraction = $this->resolveFraction($payload);

            if (! $zone instanceof EkoZone || ! $fraction instanceof WasteFraction || blank($payload['collection_date'] ?? null)) {
                $skipped++;
                Log::warning('eko_uslugi.schedule_import.row_skipped', [
                    'schedule_id' => $schedule->id,
                    'line' => $lineNumber + 1,
                ]);

                continue;
            }

            CollectionScheduleDate::query()->firstOrCreate([
                'eko_collection_schedule_id' => $schedule->id,
                'eko_zone_id' => $zone->id,
                'eko_waste_fraction_id' => $fraction->id,
                'collection_date' => CarbonImmutable::parse((string) $payload['collection_date'])->toDateString(),
            ]);

            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'rb');
        $firstLine = fgets($handle);
        fclose($handle);

        return substr_count((string) $firstLine, ';') > substr_count((string) $firstLine, ',') ? ';' : ',';
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<int, string>
     */
    private function normalizeHeaders(array $row): array
    {
        return collect($row)
            ->map(fn (mixed $header): string => str((string) $header)->trim()->lower()->snake()->toString())
            ->all();
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    private function rowPayload(array $headers, array $row): array
    {
        return collect($headers)
            ->mapWithKeys(fn (string $header, int $index): array => [$header => $row[$index] ?? null])
            ->all();
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return collect($row)->filter(fn (mixed $value): bool => filled($value))->isEmpty();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveZone(array $payload): ?EkoZone
    {
        if (filled($payload['zone_id'] ?? null)) {
            return EkoZone::query()->whereKey($payload['zone_id'])->first();
        }

        $code = $payload['zone_code'] ?? $payload['zone'] ?? null;
        $name = $payload['zone_name'] ?? $payload['zone'] ?? null;

        if (blank($code) && blank($name)) {
            return null;
        }

        return EkoZone::query()
            ->where(function ($query) use ($code, $name): void {
                $query
                    ->when(filled($code), fn ($query) => $query->orWhere('code', $code))
                    ->when(filled($name), fn ($query) => $query->orWhere('name', $name));
            })
            ->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveFraction(array $payload): ?WasteFraction
    {
        if (filled($payload['fraction_id'] ?? null)) {
            return WasteFraction::query()->whereKey($payload['fraction_id'])->first();
        }

        $name = $payload['fraction_name'] ?? $payload['fraction'] ?? null;

        if (blank($name)) {
            return null;
        }

        return WasteFraction::query()
            ->when(filled($name), fn ($query) => $query->where('name', $name))
            ->first();
    }
}
