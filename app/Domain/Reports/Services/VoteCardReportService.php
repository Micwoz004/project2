<?php

namespace App\Domain\Reports\Services;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\Vote;
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

    public function projectAgeGroupTotals(BudgetEdition $edition): Collection
    {
        Log::info('vote_card_report.project_age_groups.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $rows = Vote::query()
            ->join('vote_cards', 'votes.vote_card_id', '=', 'vote_cards.id')
            ->join('voters', 'vote_cards.voter_id', '=', 'voters.id')
            ->where('vote_cards.budget_edition_id', $edition->id)
            ->where('vote_cards.status', VoteCardStatus::Accepted->value)
            ->select([
                'votes.project_id',
                DB::raw('SUM(CASE WHEN voters.age BETWEEN 16 AND 30 THEN votes.points ELSE 0 END) as age_16_30'),
                DB::raw('SUM(CASE WHEN voters.age BETWEEN 31 AND 45 THEN votes.points ELSE 0 END) as age_31_45'),
                DB::raw('SUM(CASE WHEN voters.age BETWEEN 46 AND 60 THEN votes.points ELSE 0 END) as age_46_60'),
                DB::raw('SUM(CASE WHEN voters.age >= 61 THEN votes.points ELSE 0 END) as age_61_plus'),
                DB::raw('SUM(votes.points) as total'),
            ])
            ->groupBy('votes.project_id')
            ->orderByDesc('total')
            ->orderBy('votes.project_id')
            ->get();

        Log::info('vote_card_report.project_age_groups.success', [
            'budget_edition_id' => $edition->id,
            'projects_count' => $rows->count(),
        ]);

        return $rows;
    }

    public function projectSexTotals(BudgetEdition $edition): Collection
    {
        Log::info('vote_card_report.project_sex_totals.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $rows = Vote::query()
            ->join('vote_cards', 'votes.vote_card_id', '=', 'vote_cards.id')
            ->join('voters', 'vote_cards.voter_id', '=', 'voters.id')
            ->where('vote_cards.budget_edition_id', $edition->id)
            ->where('vote_cards.status', VoteCardStatus::Accepted->value)
            ->select([
                'votes.project_id',
                DB::raw("SUM(CASE WHEN voters.sex = 'K' OR voters.sex = 'F' THEN votes.points ELSE 0 END) as female"),
                DB::raw("SUM(CASE WHEN voters.sex = 'M' THEN votes.points ELSE 0 END) as male"),
                DB::raw("SUM(CASE WHEN voters.sex IS NULL OR voters.sex NOT IN ('K', 'F', 'M') THEN votes.points ELSE 0 END) as unknown"),
                DB::raw('SUM(votes.points) as total'),
            ])
            ->groupBy('votes.project_id')
            ->orderByDesc('total')
            ->orderBy('votes.project_id')
            ->get();

        Log::info('vote_card_report.project_sex_totals.success', [
            'budget_edition_id' => $edition->id,
            'projects_count' => $rows->count(),
        ]);

        return $rows;
    }

    public function projectCardTypeTotals(BudgetEdition $edition): Collection
    {
        Log::info('vote_card_report.project_card_type_totals.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $rows = Vote::query()
            ->join('vote_cards', 'votes.vote_card_id', '=', 'vote_cards.id')
            ->where('vote_cards.budget_edition_id', $edition->id)
            ->where('vote_cards.status', VoteCardStatus::Accepted->value)
            ->select([
                'votes.project_id',
                DB::raw('SUM(CASE WHEN vote_cards.digital = 1 THEN votes.points ELSE 0 END) as digital'),
                DB::raw('SUM(CASE WHEN vote_cards.digital = 0 THEN votes.points ELSE 0 END) as paper'),
                DB::raw('SUM(votes.points) as total'),
            ])
            ->groupBy('votes.project_id')
            ->orderByDesc('total')
            ->orderBy('votes.project_id')
            ->get();

        Log::info('vote_card_report.project_card_type_totals.success', [
            'budget_edition_id' => $edition->id,
            'projects_count' => $rows->count(),
        ]);

        return $rows;
    }
}
