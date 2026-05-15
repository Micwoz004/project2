<?php

namespace App\Domain\Reports\Exports;

use App\Domain\Reports\Services\ProjectReportService;
use Illuminate\Support\Facades\Log;

class UnsentAdvancedVerificationsCsvExporter
{
    public function __construct(
        private readonly ProjectReportService $projectReportService,
    ) {}

    public function export(string $baseUrl = 'https://sbownioski.szczecin.eu'): string
    {
        Log::info('unsent_advanced_verifications_export.csv.start');

        $rows = $this->projectReportService->unsentAdvancedVerificationRows($baseUrl);
        $handle = fopen('php://temp', 'rb+');

        fputcsv($handle, [
            'Numer wniosku',
            'Tytuł',
            'Nazwa wydziału',
            'Nazwa autora',
            'Link do projektu',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['project_number'],
                $row['title'],
                $row['department_name'],
                $row['author_name'],
                $row['project_url'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Log::info('unsent_advanced_verifications_export.csv.success', [
            'rows_count' => $rows->count(),
        ]);

        return $csv;
    }
}
