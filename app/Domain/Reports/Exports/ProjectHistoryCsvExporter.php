<?php

namespace App\Domain\Reports\Exports;

use App\Domain\Reports\Services\ProjectReportService;
use Illuminate\Support\Facades\Log;

class ProjectHistoryCsvExporter
{
    /**
     * @var array<string, string>
     */
    private const HEADERS = [
        'project_id' => 'Identyfikator wniosku',
        'project_number' => 'Numer wniosku',
        'title' => 'Tytuł',
        'project_category' => 'Kategoria projektu',
        'district' => 'Dzielnica',
        'category_reason' => 'Uzasadnienie kategorii',
        'localization' => 'Lokalizacja, miejsce realizacji projektu',
        'goal' => 'Cel projektu',
        'description' => 'Szczegółowy opis',
        'recipients' => 'Odbiorcy projektu',
        'free_of_charge' => 'Nieodpłatność projektu',
        'status' => 'Status',
        'changed_at' => 'Data zmiany',
        'changed_by' => 'Autor zmiany',
    ];

    public function __construct(
        private readonly ProjectReportService $projectReportService,
    ) {}

    public function export(): string
    {
        Log::info('project_history_export.csv.start');

        $rows = $this->projectReportService->projectHistoryRows();
        $handle = fopen('php://temp', 'rb+');

        fputcsv($handle, array_values(self::HEADERS));

        foreach ($rows as $row) {
            fputcsv($handle, array_map(
                fn (string $key) => $row[$key],
                array_keys(self::HEADERS),
            ));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Log::info('project_history_export.csv.success', [
            'rows_count' => $rows->count(),
        ]);

        return $csv;
    }
}
