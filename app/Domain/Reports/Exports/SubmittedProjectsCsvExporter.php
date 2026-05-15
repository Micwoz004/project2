<?php

namespace App\Domain\Reports\Exports;

use App\Domain\Reports\Services\ProjectReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SubmittedProjectsCsvExporter
{
    public function __construct(
        private readonly ProjectReportService $projectReportService,
    ) {}

    public function export(Carbon|string $from = '2019-07-07 00:00:00'): string
    {
        $fromDate = $from instanceof Carbon ? $from : Carbon::parse($from);

        Log::info('submitted_projects_export.csv.start', [
            'from' => $fromDate->toDateTimeString(),
        ]);

        $rows = $this->projectReportService->submittedProjectRows($fromDate);
        $handle = fopen('php://temp', 'rb+');

        fputcsv($handle, [
            'Numer wniosku',
            'Tytuł',
            'Data złożenia',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['project_number'],
                $row['title'],
                $row['submitted_at'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Log::info('submitted_projects_export.csv.success', [
            'from' => $fromDate->toDateTimeString(),
            'rows_count' => $rows->count(),
        ]);

        return $csv;
    }
}
