<?php

namespace App\Domain\BudgetEditions\Services;

use App\Domain\BudgetEditions\Enums\BudgetEditionState;
use App\Domain\BudgetEditions\Models\BudgetEdition;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class BudgetEditionStateResolver
{
    public function resolve(BudgetEdition $edition, ?CarbonInterface $now = null): BudgetEditionState
    {
        $now = $now ?? Carbon::now('Europe/Warsaw');

        if ($edition->propose_start->greaterThanOrEqualTo($now)
            || $edition->result_announcement_end->lessThanOrEqualTo($now)) {
            return BudgetEditionState::Inactive;
        }

        if ($edition->propose_start->lessThanOrEqualTo($now)
            && $edition->propose_end->greaterThanOrEqualTo($now)) {
            return BudgetEditionState::Propose;
        }

        if ($edition->propose_end->lessThanOrEqualTo($now)
            && $edition->pre_voting_verification_end->greaterThanOrEqualTo($now)) {
            return BudgetEditionState::PreVotingVerification;
        }

        if ($edition->pre_voting_verification_end->lessThanOrEqualTo($now)
            && $edition->voting_start->greaterThan($now)) {
            return BudgetEditionState::PreVotingCorrection;
        }

        if ($edition->voting_start->lessThanOrEqualTo($now)
            && $edition->voting_end->greaterThanOrEqualTo($now)) {
            return BudgetEditionState::Voting;
        }

        if ($edition->voting_end->lessThanOrEqualTo($now)
            && $edition->post_voting_verification_end->greaterThanOrEqualTo($now)) {
            return BudgetEditionState::PostVotingVerification;
        }

        if ($edition->post_voting_verification_end->lessThanOrEqualTo($now)
            && $edition->result_announcement_end->greaterThanOrEqualTo($now)) {
            return BudgetEditionState::ResultAnnouncement;
        }

        return BudgetEditionState::Inactive;
    }
}
