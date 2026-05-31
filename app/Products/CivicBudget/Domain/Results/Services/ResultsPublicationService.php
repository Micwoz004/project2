<?php

namespace App\Products\CivicBudget\Domain\Results\Services;

use App\Products\CivicBudget\Domain\BudgetEditions\Enums\BudgetEditionState;
use App\Products\CivicBudget\Domain\BudgetEditions\Models\BudgetEdition;
use App\Products\CivicBudget\Domain\BudgetEditions\Services\BudgetEditionStateResolver;
use Illuminate\Support\Facades\Log;

class ResultsPublicationService
{
    public function __construct(
        private readonly BudgetEditionStateResolver $stateResolver,
    ) {}

    public function canPublishPublicResults(BudgetEdition $edition): bool
    {
        $state = $this->stateResolver->resolve($edition);

        Log::info('results.publication.check', [
            'budget_edition_id' => $edition->id,
            'state' => $state->value,
        ]);

        return $state === BudgetEditionState::ResultAnnouncement;
    }
}
