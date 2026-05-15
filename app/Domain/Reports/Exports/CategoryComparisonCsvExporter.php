<?php

namespace App\Domain\Reports\Exports;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Results\Services\ResultsCalculator;
use Illuminate\Support\Facades\Log;

class CategoryComparisonCsvExporter
{
    public function __construct(
        private readonly ResultsCalculator $resultsCalculator,
    ) {}

    public function export(BudgetEdition $edition): string
    {
        Log::info('category_comparison_export.csv.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $rows = $this->resultsCalculator->categoryComparisonTotals($edition);
        $handle = fopen('php://temp', 'rb+');

        fputcsv($handle, [
            'category_id',
            'category_name',
            'primary_category_points',
            'multi_category_points',
            'difference',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row->category_id,
                $row->name,
                $row->primary_points,
                $row->multi_category_points,
                $row->difference,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Log::info('category_comparison_export.csv.success', [
            'budget_edition_id' => $edition->id,
            'rows_count' => $rows->count(),
        ]);

        return $csv;
    }
}
