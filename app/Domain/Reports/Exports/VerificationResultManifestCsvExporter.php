<?php

namespace App\Domain\Reports\Exports;

use App\Domain\Reports\Services\ProjectReportService;
use Illuminate\Support\Facades\Log;

class VerificationResultManifestCsvExporter
{
    public function __construct(
        private readonly ProjectReportService $projectReportService,
    ) {}

    public function export(): string
    {
        Log::info('verification_result_manifest_export.csv.start');

        $rows = $this->projectReportService->verificationResultManifestRows();
        $handle = fopen('php://temp', 'rb+');

        fputcsv($handle, [
            'project_id',
            'project_number',
            'title',
            'formal_present',
            'initial_sent_count',
            'final_sent_count',
            'consultation_sent_count',
            'file_name',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['project_id'],
                $row['project_number'],
                $row['title'],
                $row['formal_present'],
                $row['initial_sent_count'],
                $row['final_sent_count'],
                $row['consultation_sent_count'],
                $row['file_name'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Log::info('verification_result_manifest_export.csv.success', [
            'rows_count' => $rows->count(),
        ]);

        return $csv;
    }
}
