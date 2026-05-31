<?php

namespace App\Products\CivicBudget\Http\Controllers\Public;

use App\Products\CivicBudget\Domain\BudgetEditions\Models\BudgetEdition;
use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Products\CivicBudget\Domain\Reports\Exports\PublicResultsCsvExporter;
use App\Products\CivicBudget\Domain\Results\Services\ResultsCalculator;
use App\Products\CivicBudget\Domain\Results\Services\ResultsPublicationService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicResultsController extends Controller
{
    public function index(ResultsCalculator $resultsCalculator, ResultsPublicationService $publicationService): View
    {
        $edition = BudgetEdition::query()->latest('result_announcement_end')->first();
        $totals = collect();
        $projects = collect();
        $resultsPublished = false;

        if ($edition && $publicationService->canPublishPublicResults($edition)) {
            $resultsPublished = true;
            $totals = $resultsCalculator->projectTotals($edition);
            $projects = Project::query()
                ->with('area')
                ->whereIn('id', $totals->pluck('project_id'))
                ->get()
                ->keyBy('id');
        }

        return view('public.results.index', [
            'edition' => $edition,
            'totals' => $totals,
            'projects' => $projects,
            'resultsPublished' => $resultsPublished,
        ]);
    }

    public function export(PublicResultsCsvExporter $exporter, ResultsPublicationService $publicationService): StreamedResponse
    {
        $edition = BudgetEdition::query()->latest('result_announcement_end')->firstOrFail();

        abort_unless($publicationService->canPublishPublicResults($edition), 404);

        return response()->streamDownload(
            fn () => print $exporter->export($edition),
            'wyniki-publiczne.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}
