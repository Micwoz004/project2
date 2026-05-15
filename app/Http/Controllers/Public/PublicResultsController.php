<?php

namespace App\Http\Controllers\Public;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Models\Project;
use App\Domain\Results\Services\ResultsCalculator;
use App\Domain\Results\Services\ResultsPublicationService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

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
}
