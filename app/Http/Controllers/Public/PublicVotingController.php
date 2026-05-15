<?php

namespace App\Http\Controllers\Public;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Models\Project;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PublicVotingController extends Controller
{
    public function welcome(): View
    {
        $edition = BudgetEdition::query()->latest('voting_start')->first();

        return view('public.voting.welcome', [
            'edition' => $edition,
            'localProjects' => Project::query()
                ->with('area')
                ->pickedForVoting()
                ->whereHas('area', fn ($query) => $query->where('is_local', true))
                ->orderBy('number_drawn')
                ->get(),
            'citywideProjects' => Project::query()
                ->with('area')
                ->pickedForVoting()
                ->whereHas('area', fn ($query) => $query->where('is_local', false))
                ->orderBy('number_drawn')
                ->get(),
        ]);
    }
}
