<?php

namespace App\Http\Controllers\Public;

use App\Domain\BudgetEditions\Enums\BudgetEditionState;
use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\BudgetEditions\Services\BudgetEditionStateResolver;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Settings\Models\PublicAnnouncement;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PublicHomeController extends Controller
{
    public function __invoke(BudgetEditionStateResolver $stateResolver): View
    {
        Log::info('public_home.show.start');

        $edition = BudgetEdition::query()->latest('propose_start')->first();
        $state = $edition instanceof BudgetEdition
            ? $stateResolver->resolve($edition)
            : BudgetEditionState::Inactive;

        $projectCount = $edition instanceof BudgetEdition
            ? Project::query()
                ->where('budget_edition_id', $edition->id)
                ->whereIn('status', [ProjectStatus::Submitted->value, ProjectStatus::Picked->value])
                ->count()
            : 0;

        $pickedCount = $edition instanceof BudgetEdition
            ? Project::query()
                ->where('budget_edition_id', $edition->id)
                ->pickedForVoting()
                ->count()
            : 0;

        $announcements = PublicAnnouncement::query()
            ->published()
            ->latest('published_at')
            ->limit(3)
            ->get();

        Log::info('public_home.show.success', [
            'budget_edition_id' => $edition?->id,
            'state' => $state->value,
            'announcements_count' => $announcements->count(),
        ]);

        return view('public.home', [
            'edition' => $edition,
            'state' => $state,
            'projectCount' => $projectCount,
            'pickedCount' => $pickedCount,
            'announcements' => $announcements,
        ]);
    }
}
