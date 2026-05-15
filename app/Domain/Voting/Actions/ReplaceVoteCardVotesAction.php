<?php

namespace App\Domain\Voting\Actions;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Voting\Models\VoteCard;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReplaceVoteCardVotesAction
{
    private const MAX_LOCAL_POINTS = 1;

    private const MAX_CITYWIDE_POINTS = 1;

    /**
     * @param  array<int, int>  $localProjectIds
     * @param  array<int, int>  $citywideProjectIds
     */
    public function execute(
        VoteCard $voteCard,
        array $localProjectIds,
        array $citywideProjectIds,
        User $operator,
        bool $confirmMissingCategory = false,
    ): VoteCard {
        $localProjectIds = $this->normalizeProjectIds($localProjectIds);
        $citywideProjectIds = $this->normalizeProjectIds($citywideProjectIds);

        Log::info('vote_card.votes.replace.start', [
            'vote_card_id' => $voteCard->id,
            'operator_id' => $operator->id,
            'local_count' => count($localProjectIds),
            'citywide_count' => count($citywideProjectIds),
        ]);

        if (! $this->canManageVoteCards($operator)) {
            Log::warning('vote_card.votes.replace.rejected_permission', [
                'vote_card_id' => $voteCard->id,
                'operator_id' => $operator->id,
            ]);

            throw new DomainException('Brak uprawnień do edycji głosów na karcie.');
        }

        $this->assertVoteSelectionAllowed($voteCard, $localProjectIds, $citywideProjectIds, $confirmMissingCategory);

        return DB::transaction(function () use ($voteCard, $localProjectIds, $citywideProjectIds, $operator): VoteCard {
            $deletedVotes = $voteCard->votes()->delete();

            foreach (array_merge($localProjectIds, $citywideProjectIds) as $projectId) {
                $voteCard->votes()->create([
                    'voter_id' => $voteCard->voter_id,
                    'project_id' => $projectId,
                    'points' => 1,
                ]);
            }

            $voteCard->forceFill([
                'checkout_user_id' => $operator->id,
                'checkout_date_time' => now(),
            ])->save();

            Log::info('vote_card.votes.replace.success', [
                'vote_card_id' => $voteCard->id,
                'operator_id' => $operator->id,
                'deleted_votes' => $deletedVotes,
                'new_votes' => count($localProjectIds) + count($citywideProjectIds),
            ]);

            return $voteCard->refresh();
        });
    }

    /**
     * @param  array<int, int>  $localProjectIds
     * @param  array<int, int>  $citywideProjectIds
     */
    private function assertVoteSelectionAllowed(
        VoteCard $voteCard,
        array $localProjectIds,
        array $citywideProjectIds,
        bool $confirmMissingCategory,
    ): void {
        if ($localProjectIds === [] && $citywideProjectIds === []) {
            Log::warning('vote_card.votes.replace.rejected_empty_vote', [
                'vote_card_id' => $voteCard->id,
            ]);

            throw new DomainException('Musisz wskazać przynajmniej jeden projekt.');
        }

        if (($localProjectIds === []) !== ($citywideProjectIds === []) && ! $confirmMissingCategory) {
            Log::warning('vote_card.votes.replace.rejected_missing_category_confirmation', [
                'vote_card_id' => $voteCard->id,
                'local_count' => count($localProjectIds),
                'citywide_count' => count($citywideProjectIds),
            ]);

            throw new DomainException('Brak głosu w jednej kategorii wymaga potwierdzenia.');
        }

        if (count($localProjectIds) > self::MAX_LOCAL_POINTS || count($citywideProjectIds) > self::MAX_CITYWIDE_POINTS) {
            Log::warning('vote_card.votes.replace.rejected_points_limit', [
                'vote_card_id' => $voteCard->id,
                'local_count' => count($localProjectIds),
                'citywide_count' => count($citywideProjectIds),
            ]);

            throw new DomainException('Przekroczono limit głosów.');
        }

        $this->assertProjectsArePicked($voteCard, $localProjectIds, true);
        $this->assertProjectsArePicked($voteCard, $citywideProjectIds, false);
    }

    /**
     * @param  array<int, int>  $projectIds
     */
    private function assertProjectsArePicked(VoteCard $voteCard, array $projectIds, bool $local): void
    {
        if ($projectIds === []) {
            return;
        }

        $projects = Project::query()
            ->with('area')
            ->where('budget_edition_id', $voteCard->budget_edition_id)
            ->whereIn('id', $projectIds)
            ->where('status', ProjectStatus::Picked->value)
            ->get();

        if ($projects->count() !== count($projectIds)) {
            Log::warning('vote_card.votes.replace.rejected_project_not_picked', [
                'vote_card_id' => $voteCard->id,
                'project_ids' => $projectIds,
            ]);

            throw new DomainException('Wybrany projekt nie znajduje się na liście do głosowania.');
        }

        $mismatched = $projects->first(fn (Project $project): bool => $project->area->is_local !== $local);

        if ($mismatched instanceof Project) {
            Log::warning('vote_card.votes.replace.rejected_area_type_mismatch', [
                'vote_card_id' => $voteCard->id,
                'project_id' => $mismatched->id,
            ]);

            throw new DomainException('Wybrany projekt należy do innej kategorii głosu.');
        }
    }

    /**
     * @param  array<int, int>  $projectIds
     * @return array<int, int>
     */
    private function normalizeProjectIds(array $projectIds): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn ($projectId): int => (int) $projectId, $projectIds),
            static fn (int $projectId): bool => $projectId > 0,
        )));
    }

    private function canManageVoteCards(User $operator): bool
    {
        return $operator->can('vote_cards.manage')
            || $operator->can('voting.manage')
            || $operator->hasAnyRole(['admin', 'bdo']);
    }
}
