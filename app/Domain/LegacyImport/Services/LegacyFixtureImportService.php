<?php

namespace App\Domain\LegacyImport\Services;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\LegacyImport\Models\LegacyImportBatch;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Models\ProjectCostItem;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\Vote;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Models\Voter;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LegacyFixtureImportService
{
    /**
     * @param  array<string, list<array<string, mixed>>>  $payload
     */
    public function import(array $payload, string $source = 'fixture'): LegacyImportBatch
    {
        Log::info('legacy_import.fixture.start', [
            'source' => $source,
        ]);

        return DB::transaction(function () use ($payload, $source): LegacyImportBatch {
            $batch = LegacyImportBatch::query()->create([
                'source_path' => $source,
                'started_at' => now(),
            ]);

            $stats = [
                'taskgroups' => $this->importTaskGroups($payload['taskgroups'] ?? []),
                'tasktypes' => $this->importTaskTypes($payload['tasktypes'] ?? []),
                'categories' => $this->importCategories($payload['categories'] ?? []),
                'tasks' => $this->importTasks($payload['tasks'] ?? []),
                'taskscategories' => $this->importTasksCategories($payload['taskscategories'] ?? []),
                'taskcosts' => $this->importTaskCosts($payload['taskcosts'] ?? []),
                'voters' => $this->importVoters($payload['voters'] ?? []),
                'votecards' => $this->importVoteCards($payload['votecards'] ?? []),
                'votes' => $this->importVotes($payload['votes'] ?? []),
            ];

            $batch->forceFill([
                'stats' => $stats,
                'finished_at' => now(),
            ])->save();

            Log::info('legacy_import.fixture.success', [
                'source' => $source,
                'batch_id' => $batch->id,
                'tables_count' => count($stats),
            ]);

            return $batch->refresh();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importTaskGroups(array $rows): int
    {
        foreach ($rows as $row) {
            BudgetEdition::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'propose_start' => Arr::get($row, 'proposeStart'),
                'propose_end' => Arr::get($row, 'proposeEnd'),
                'pre_voting_verification_end' => Arr::get($row, 'preVotingVerificationEnd'),
                'voting_start' => Arr::get($row, 'votingStart'),
                'voting_end' => Arr::get($row, 'votingEnd'),
                'post_voting_verification_end' => Arr::get($row, 'postVotingVerificationEnd'),
                'result_announcement_end' => Arr::get($row, 'resultAnnouncementEnd'),
                'current_digital_card_no' => (int) Arr::get($row, 'currentDigitalCardNo', 0),
                'current_paper_card_no' => (int) Arr::get($row, 'currentPaperCardNo', 0),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importTaskTypes(array $rows): int
    {
        foreach ($rows as $row) {
            ProjectArea::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'name' => Arr::get($row, 'name'),
                'symbol' => Arr::get($row, 'symbol'),
                'name_shortcut' => Arr::get($row, 'nameShortcut'),
                'is_local' => (bool) Arr::get($row, 'local', true),
                'cost_limit' => (int) Arr::get($row, 'costLimit', 0),
                'cost_limit_small' => Arr::get($row, 'costLimitSmall', 0),
                'cost_limit_big' => Arr::get($row, 'costLimitBig', 0),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importCategories(array $rows): int
    {
        foreach ($rows as $row) {
            Category::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'name' => Arr::get($row, 'name'),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importTasks(array $rows): int
    {
        foreach ($rows as $row) {
            $budgetEdition = $this->budgetEdition((int) Arr::get($row, 'taskGroupId'));
            $area = $this->projectArea((int) Arr::get($row, 'taskTypeId'));
            $category = $this->category((int) Arr::get($row, 'categoryId'));

            Project::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'budget_edition_id' => $budgetEdition->id,
                'project_area_id' => $area->id,
                'category_id' => $category->id,
                'number' => Arr::get($row, 'number'),
                'number_drawn' => Arr::get($row, 'numberDrawn'),
                'title' => Arr::get($row, 'title'),
                'localization' => Arr::get($row, 'localization'),
                'description' => Arr::get($row, 'description'),
                'goal' => Arr::get($row, 'goal'),
                'argumentation' => Arr::get($row, 'argumentation'),
                'status' => ProjectStatus::from((int) Arr::get($row, 'status')),
                'cost' => Arr::get($row, 'cost'),
                'cost_formatted' => Arr::get($row, 'costFormatted'),
                'is_support_list' => (bool) Arr::get($row, 'isSupportList', false),
                'is_picked' => (bool) Arr::get($row, 'isPicked', false),
                'is_hidden' => (bool) Arr::get($row, 'isHidden', false),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importTaskCosts(array $rows): int
    {
        foreach ($rows as $row) {
            $project = $this->project((int) Arr::get($row, 'taskId'));

            ProjectCostItem::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'project_id' => $project->id,
                'description' => Arr::get($row, 'description'),
                'amount' => Arr::get($row, 'amount', 0),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importTasksCategories(array $rows): int
    {
        foreach ($rows as $row) {
            $project = $this->project((int) Arr::get($row, 'taskId'));
            $category = $this->category((int) Arr::get($row, 'categoryId'));

            $project->categories()->syncWithoutDetaching([$category->id]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importVoters(array $rows): int
    {
        foreach ($rows as $row) {
            Voter::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'pesel' => Arr::get($row, 'pesel'),
                'first_name' => Arr::get($row, 'firstName'),
                'last_name' => Arr::get($row, 'lastName'),
                'birth_date' => Arr::get($row, 'birthDate'),
                'sex' => Arr::get($row, 'sex'),
                'age' => Arr::get($row, 'age'),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importVoteCards(array $rows): int
    {
        foreach ($rows as $row) {
            $budgetEdition = $this->budgetEdition((int) Arr::get($row, 'taskGroupId'));
            $voter = $this->voter((int) Arr::get($row, 'voterId'));

            VoteCard::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'budget_edition_id' => $budgetEdition->id,
                'voter_id' => $voter->id,
                'card_no' => Arr::get($row, 'cardNo'),
                'digital' => (bool) Arr::get($row, 'digital', true),
                'status' => VoteCardStatus::from((int) Arr::get($row, 'status')),
                'no_pesel_number' => (bool) Arr::get($row, 'noPeselNumber', false),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importVotes(array $rows): int
    {
        foreach ($rows as $row) {
            $voteCard = $this->voteCard((int) Arr::get($row, 'voteCardId'));
            $voter = $this->voter((int) Arr::get($row, 'voterId'));
            $project = $this->project((int) Arr::get($row, 'taskId'));

            Vote::query()->updateOrCreate([
                'legacy_id' => $this->legacyId($row),
            ], [
                'vote_card_id' => $voteCard->id,
                'voter_id' => $voter->id,
                'project_id' => $project->id,
                'points' => (int) Arr::get($row, 'points', 1),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function legacyId(array $row): int
    {
        return (int) Arr::get($row, 'id');
    }

    private function budgetEdition(int $legacyId): BudgetEdition
    {
        return $this->findLegacy(BudgetEdition::class, $legacyId, 'taskgroups');
    }

    private function projectArea(int $legacyId): ProjectArea
    {
        return $this->findLegacy(ProjectArea::class, $legacyId, 'tasktypes');
    }

    private function category(int $legacyId): Category
    {
        return $this->findLegacy(Category::class, $legacyId, 'categories');
    }

    private function project(int $legacyId): Project
    {
        return $this->findLegacy(Project::class, $legacyId, 'tasks');
    }

    private function voter(int $legacyId): Voter
    {
        return $this->findLegacy(Voter::class, $legacyId, 'voters');
    }

    private function voteCard(int $legacyId): VoteCard
    {
        return $this->findLegacy(VoteCard::class, $legacyId, 'votecards');
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @return TModel
     */
    private function findLegacy(string $model, int $legacyId, string $table): mixed
    {
        $record = $model::query()->where('legacy_id', $legacyId)->first();

        if ($record === null) {
            Log::warning('legacy_import.fixture.missing_relation', [
                'table' => $table,
                'legacy_id' => $legacyId,
            ]);

            throw new DomainException("Brak rekordu legacy {$table}:{$legacyId}.");
        }

        return $record;
    }
}
