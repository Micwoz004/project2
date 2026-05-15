<?php

namespace App\Domain\Reports\Services;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoteCardReportService
{
    public function statusCounts(BudgetEdition $edition): Collection
    {
        Log::info('vote_card_report.status_counts.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $counts = VoteCard::query()
            ->select(['status', DB::raw('COUNT(*) as cards_count')])
            ->where('budget_edition_id', $edition->id)
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->mapWithKeys(fn (VoteCard $voteCard) => [
                $voteCard->status->value => (int) $voteCard->cards_count,
            ]);

        Log::info('vote_card_report.status_counts.success', [
            'budget_edition_id' => $edition->id,
            'statuses_count' => $counts->count(),
        ]);

        return $counts;
    }

    public function acceptedVoterDemographics(BudgetEdition $edition): array
    {
        Log::info('vote_card_report.demographics.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $baseQuery = VoteCard::query()
            ->join('voters', 'vote_cards.voter_id', '=', 'voters.id')
            ->where('vote_cards.budget_edition_id', $edition->id)
            ->where('vote_cards.status', VoteCardStatus::Accepted->value);

        $sex = (clone $baseQuery)
            ->select(['voters.sex', DB::raw('COUNT(*) as voters_count')])
            ->whereNotNull('voters.sex')
            ->groupBy('voters.sex')
            ->orderBy('voters.sex')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->sex => (int) $row->voters_count]);

        $ageBuckets = [
            'under_18' => (clone $baseQuery)->where('voters.age', '<', 18)->count(),
            '18_35' => (clone $baseQuery)->whereBetween('voters.age', [18, 35])->count(),
            '36_60' => (clone $baseQuery)->whereBetween('voters.age', [36, 60])->count(),
            'over_60' => (clone $baseQuery)->where('voters.age', '>', 60)->count(),
            'unknown' => (clone $baseQuery)->whereNull('voters.age')->count(),
        ];

        Log::info('vote_card_report.demographics.success', [
            'budget_edition_id' => $edition->id,
            'sex_groups_count' => $sex->count(),
        ]);

        return [
            'sex' => $sex,
            'age_buckets' => $ageBuckets,
        ];
    }
}
