<?php

namespace App\Domain\Reports\Exports;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Reports\Services\VoteCardReportService;
use Illuminate\Support\Facades\Log;

class AdminVoteCardsCsvExporter
{
    public function __construct(
        private readonly VoteCardReportService $voteCardReportService,
    ) {}

    public function export(BudgetEdition $edition): string
    {
        Log::info('admin_vote_cards_export.csv.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $rows = $this->voteCardReportService->adminVoteCardRows($edition);
        $handle = fopen('php://temp', 'rb+');

        fputcsv($handle, [
            'ID karty',
            'Typ karty',
            'PESEL',
            'Imie głosującego',
            'Nazwisko głosującego',
            'Oświadczenie zamieszkania',
            'Status',
            'Uwagi',
            'Data dodania',
            'Data modyfikacji',
            'IP',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['card_id'],
                $row['card_type'],
                $row['pesel'],
                $row['first_name'],
                $row['last_name'],
                $row['city_statement'],
                $row['status'],
                $row['notes'],
                $row['created_at'],
                $row['updated_at'],
                $row['ip'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Log::info('admin_vote_cards_export.csv.success', [
            'budget_edition_id' => $edition->id,
            'rows_count' => $rows->count(),
        ]);

        return $csv;
    }
}
