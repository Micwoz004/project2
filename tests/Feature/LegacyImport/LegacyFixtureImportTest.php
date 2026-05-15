<?php

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\LegacyImport\Models\LegacyImportBatch;
use App\Domain\LegacyImport\Services\LegacyFixtureImportService;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Models\ProjectCostItem;
use App\Domain\Results\Services\ResultsCalculator;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\Vote;
use App\Domain\Voting\Models\VoteCard;

it('imports a legacy fixture with ids statuses relations and result totals', function (): void {
    $payload = [
        'taskgroups' => [[
            'id' => 10,
            'name' => 'SBO 2025',
            'proposeStart' => '2025-01-01 00:00:00',
            'proposeEnd' => '2025-02-01 00:00:00',
            'preVotingVerificationEnd' => '2025-03-01 00:00:00',
            'votingStart' => '2025-04-01 00:00:00',
            'votingEnd' => '2025-04-15 23:59:59',
            'postVotingVerificationEnd' => '2025-05-01 00:00:00',
            'resultAnnouncementEnd' => '2025-06-01 00:00:00',
        ]],
        'tasktypes' => [[
            'id' => 20,
            'name' => 'Pogodno',
            'symbol' => 'P1',
            'local' => true,
            'costLimit' => 1000000,
        ]],
        'categories' => [[
            'id' => 30,
            'name' => 'Zieleń',
        ], [
            'id' => 31,
            'name' => 'Sport',
        ]],
        'tasks' => [[
            'id' => 40,
            'taskGroupId' => 10,
            'taskTypeId' => 20,
            'categoryId' => 30,
            'number' => 7,
            'numberDrawn' => 11,
            'title' => 'Park kieszonkowy',
            'localization' => 'Szczecin Pogodno',
            'description' => 'Opis',
            'goal' => 'Cel',
            'argumentation' => 'Uzasadnienie',
            'status' => ProjectStatus::Picked->value,
            'cost' => '1000',
            'costFormatted' => 1000,
            'isSupportList' => true,
            'isPicked' => true,
        ]],
        'taskcosts' => [[
            'id' => 50,
            'taskId' => 40,
            'description' => 'Nasadzenia',
            'amount' => 1000,
        ]],
        'taskscategories' => [[
            'taskId' => 40,
            'categoryId' => 30,
        ], [
            'taskId' => 40,
            'categoryId' => 31,
        ]],
        'voters' => [[
            'id' => 60,
            'pesel' => '44051401458',
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
            'birthDate' => '1944-05-14',
            'sex' => 'M',
            'age' => 80,
        ]],
        'votecards' => [[
            'id' => 70,
            'taskGroupId' => 10,
            'voterId' => 60,
            'cardNo' => 101,
            'digital' => true,
            'status' => VoteCardStatus::Accepted->value,
        ]],
        'votes' => [[
            'id' => 80,
            'voteCardId' => 70,
            'voterId' => 60,
            'taskId' => 40,
            'points' => 1,
        ]],
    ];

    $batch = app(LegacyFixtureImportService::class)->import($payload, 'unit-fixture');
    $edition = BudgetEdition::query()->where('legacy_id', 10)->firstOrFail();
    $project = Project::query()->where('legacy_id', 40)->firstOrFail();
    $voteCard = VoteCard::query()->where('legacy_id', 70)->firstOrFail();
    $totals = app(ResultsCalculator::class)->projectTotals($edition);

    expect($batch->source_path)->toBe('unit-fixture')
        ->and($batch->finished_at)->not->toBeNull()
        ->and($batch->stats['tasks'])->toBe(1)
        ->and($batch->stats['taskscategories'])->toBe(2)
        ->and($edition->legacy_id)->toBe(10)
        ->and(ProjectArea::query()->where('legacy_id', 20)->firstOrFail()->is_local)->toBeTrue()
        ->and($project->status)->toBe(ProjectStatus::Picked)
        ->and($project->budget_edition_id)->toBe($edition->id)
        ->and($project->categories()->pluck('categories.id')->sort()->values()->all())
        ->toBe(Category::query()->whereIn('legacy_id', [30, 31])->pluck('id')->sort()->values()->all())
        ->and(ProjectCostItem::query()->where('legacy_id', 50)->firstOrFail()->project_id)->toBe($project->id)
        ->and($voteCard->status)->toBe(VoteCardStatus::Accepted)
        ->and(Vote::query()->where('legacy_id', 80)->firstOrFail()->project_id)->toBe($project->id)
        ->and((int) $totals->first()->points)->toBe(1);
});

it('keeps fixture import idempotent by legacy ids', function (): void {
    $payload = [
        'taskgroups' => [[
            'id' => 10,
            'name' => 'SBO 2025',
            'proposeStart' => '2025-01-01 00:00:00',
            'proposeEnd' => '2025-02-01 00:00:00',
            'preVotingVerificationEnd' => '2025-03-01 00:00:00',
            'votingStart' => '2025-04-01 00:00:00',
            'votingEnd' => '2025-04-15 23:59:59',
            'postVotingVerificationEnd' => '2025-05-01 00:00:00',
            'resultAnnouncementEnd' => '2025-06-01 00:00:00',
        ]],
    ];

    app(LegacyFixtureImportService::class)->import($payload, 'first-pass');
    app(LegacyFixtureImportService::class)->import($payload, 'second-pass');

    expect(BudgetEdition::query()->where('legacy_id', 10)->count())->toBe(1)
        ->and(LegacyImportBatch::query()->count())->toBe(2);
});
