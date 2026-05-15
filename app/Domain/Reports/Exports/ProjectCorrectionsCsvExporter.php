<?php

namespace App\Domain\Reports\Exports;

use App\Domain\Reports\Services\ProjectReportService;
use Illuminate\Support\Facades\Log;

class ProjectCorrectionsCsvExporter
{
    /**
     * @var array<string, string>
     */
    private const HEADERS = [
        'title' => 'Tytuł',
        'taskTypeId' => 'Obszary Lokalne',
        'localization' => 'Lokalizacja projektu',
        'mapData' => 'Mapka projektu',
        'goal' => 'Cel i uzasadnienie projektu',
        'description' => 'Szczegółowy opis',
        'argumentation' => 'Uzasadnienie projektu',
        'availability' => 'Ogólnodostępność projektu',
        'recipients' => 'Odbiorcy projektu',
        'freeOfCharge' => 'Nieodpłatność projektu',
        'cost' => 'Szacunkowe koszty projektu',
        'supportAttachment' => 'Załącznik - lista poparcia',
        'agreementAttachment' => 'Załączniki - zgoda właściciela',
        'mapAttachment' => 'Załączniki - mapka',
        'parentAgreementAttachment' => 'Załączniki - zgoda rodzica/opiekuna',
        'attachments' => 'Załączniki - inne',
        'categoryId' => 'Kategoria projektu',
        'notes' => 'Informacje dla autora',
        'createdAt' => 'Data utworzenia odwołania',
        'correctionDeadline' => 'Termin zakończenia wprowadzania zmian',
    ];

    public function __construct(
        private readonly ProjectReportService $projectReportService,
    ) {}

    public function export(): string
    {
        Log::info('project_corrections_export.csv.start');

        $rows = $this->projectReportService->projectCorrectionRows();
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

        Log::info('project_corrections_export.csv.success', [
            'rows_count' => $rows->count(),
        ]);

        return $csv;
    }
}
