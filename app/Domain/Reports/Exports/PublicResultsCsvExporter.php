<?php

namespace App\Domain\Reports\Exports;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Models\Project;
use App\Domain\Results\Services\ResultsCalculator;
use Illuminate\Support\Facades\Log;

class PublicResultsCsvExporter
{
    public function __construct(
        private readonly ResultsCalculator $resultsCalculator,
    ) {}

    public function export(BudgetEdition $edition): string
    {
        Log::info('public_results_export.csv.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $totals = $this->resultsCalculator->projectTotals($edition);
        $projects = Project::query()
            ->with('area')
            ->whereIn('id', $totals->pluck('project_id'))
            ->get()
            ->keyBy('id');

        $handle = fopen('php://temp', 'rb+');
        fputcsv($handle, ['project_id', 'project_number', 'title', 'area', 'points']);

        foreach ($totals as $row) {
            $project = $projects->get($row->project_id);

            fputcsv($handle, [
                $row->project_id,
                $project?->number,
                $project?->title,
                $project?->area?->name,
                (int) $row->points,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Log::info('public_results_export.csv.success', [
            'budget_edition_id' => $edition->id,
            'rows_count' => $totals->count(),
        ]);

        return $csv;
    }
}
