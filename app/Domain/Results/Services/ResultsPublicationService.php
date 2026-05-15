<?php

namespace App\Domain\Results\Services;

use App\Domain\BudgetEditions\Enums\BudgetEditionState;
use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\BudgetEditions\Services\BudgetEditionStateResolver;
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
