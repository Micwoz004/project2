<?php

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Files\Models\ProjectFile;
use App\Domain\LegacyImport\Models\LegacyImportBatch;
use App\Domain\LegacyImport\Services\LegacyFixtureImportService;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Models\ProjectCoauthor;
use App\Domain\Projects\Models\ProjectCostItem;
use App\Domain\Results\Services\ResultsCalculator;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Enums\BoardType;
use App\Domain\Verification\Enums\VerificationAssignmentType;
use App\Domain\Verification\Models\BoardVoteRejection;
use App\Domain\Verification\Models\ConsultationVerification;
use App\Domain\Verification\Models\FinalMeritVerification;
use App\Domain\Verification\Models\FormalVerification;
use App\Domain\Verification\Models\InitialMeritVerification;
use App\Domain\Verification\Models\ProjectBoardVote;
use App\Domain\Verification\Models\VerificationAssignment;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\Vote;
use App\Domain\Voting\Models\VoteCard;
use App\Models\User;

it('imports a legacy fixture with ids statuses relations and result totals', function (): void {
    $department = Department::query()->create([
        'legacy_id' => 500,
        'name' => 'Wydział testowy',
    ]);
    $boardUser = User::factory()->create([
        'legacy_id' => 600,
    ]);

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
        'files' => [[
            'id' => 90,
            'taskId' => 40,
            'filename' => 'support.pdf',
            'originalName' => 'Lista poparcia.pdf',
            'type' => 1,
            'isTaskFormAttachment' => true,
        ]],
        'filesprivate' => [[
            'id' => 91,
            'taskId' => 40,
            'filename' => 'private.pdf',
            'originalName' => 'Prywatny.pdf',
            'type' => 3,
        ]],
        'cocreators' => [[
            'id' => 92,
            'taskId' => 40,
            'firstName' => 'Anna',
            'lastName' => 'Nowak',
            'email' => 'anna@example.test',
            'personalDataAgree' => true,
            'confirm' => true,
        ]],
        'taskverification' => [[
            'id' => 93,
            'taskId' => 40,
            'status' => 2,
            'result' => true,
            'resultComments' => 'Formalnie poprawny',
            'isPublic' => true,
            'answers' => ['hasSupportAttachment' => true],
        ]],
        'taskinitialmeritverification' => [[
            'id' => 94,
            'taskId' => 40,
            'departmentId' => 500,
            'status' => 2,
            'result' => true,
            'resultComments' => 'Wstępnie pozytywny',
        ]],
        'taskfinishmeritverification' => [[
            'id' => 95,
            'taskId' => 40,
            'departmentId' => 500,
            'status' => 2,
            'result' => true,
            'resultComments' => 'Końcowo pozytywny',
        ]],
        'taskconsultation' => [[
            'id' => 97,
            'taskId' => 40,
            'departmentId' => 500,
            'status' => 2,
            'result' => true,
            'resultComments' => 'Konsultacja pozytywna',
        ]],
        'taskdepartmentassignment' => [[
            'id' => 96,
            'taskId' => 40,
            'departmentId' => 500,
            'type' => VerificationAssignmentType::MeritInitial->value,
            'deadline' => '2025-03-10 12:00:00',
        ]],
        'zkvotes' => [[
            'id' => 98,
            'taskId' => 40,
            'userId' => 600,
            'choice' => 1,
            'comment' => 'Za',
        ]],
        'atvotes' => [[
            'id' => 99,
            'taskId' => 40,
            'userId' => 600,
            'choice' => 2,
            'comment' => 'Do głosowania',
        ]],
        'otvotes' => [[
            'id' => 100,
            'taskId' => 40,
            'userId' => 600,
            'choice' => 4,
            'comment' => 'Akceptacja',
        ]],
        'atotvotesrejection' => [[
            'id' => 101,
            'taskId' => 40,
            'userId' => 600,
            'boardType' => BoardType::At->value,
            'comment' => 'Powód odrzucenia',
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
        ->and($batch->stats['files'])->toBe(1)
        ->and($batch->stats['filesprivate'])->toBe(1)
        ->and($batch->stats['cocreators'])->toBe(1)
        ->and($batch->stats['taskverification'])->toBe(1)
        ->and($batch->stats['taskinitialmeritverification'])->toBe(1)
        ->and($batch->stats['taskfinishmeritverification'])->toBe(1)
        ->and($batch->stats['taskconsultation'])->toBe(1)
        ->and($batch->stats['taskdepartmentassignment'])->toBe(1)
        ->and($batch->stats['zkvotes'])->toBe(1)
        ->and($batch->stats['atvotes'])->toBe(1)
        ->and($batch->stats['otvotes'])->toBe(1)
        ->and($batch->stats['atotvotesrejection'])->toBe(1)
        ->and($edition->legacy_id)->toBe(10)
        ->and(ProjectArea::query()->where('legacy_id', 20)->firstOrFail()->is_local)->toBeTrue()
        ->and($project->status)->toBe(ProjectStatus::Picked)
        ->and($project->budget_edition_id)->toBe($edition->id)
        ->and($project->categories()->pluck('categories.id')->sort()->values()->all())
        ->toBe(Category::query()->whereIn('legacy_id', [30, 31])->pluck('id')->sort()->values()->all())
        ->and(ProjectCostItem::query()->where('legacy_id', 50)->firstOrFail()->project_id)->toBe($project->id)
        ->and(ProjectFile::query()->where('legacy_id', 90)->firstOrFail()->is_private)->toBeFalse()
        ->and(ProjectFile::query()->where('legacy_id', 91)->firstOrFail()->is_private)->toBeTrue()
        ->and(ProjectCoauthor::query()->where('legacy_id', 92)->firstOrFail()->confirm)->toBeTrue()
        ->and(FormalVerification::query()->where('legacy_id', 93)->firstOrFail()->answers)->toBe(['hasSupportAttachment' => true])
        ->and(InitialMeritVerification::query()->where('legacy_id', 94)->firstOrFail()->department_id)->toBe($department->id)
        ->and(FinalMeritVerification::query()->where('legacy_id', 95)->firstOrFail()->result_comments)->toBe('Końcowo pozytywny')
        ->and(ConsultationVerification::query()->where('legacy_id', 97)->firstOrFail()->department_id)->toBe($department->id)
        ->and(VerificationAssignment::query()->where('legacy_id', 96)->firstOrFail()->type)->toBe(VerificationAssignmentType::MeritInitial)
        ->and(ProjectBoardVote::query()->where('legacy_id', 98)->firstOrFail()->user_id)->toBe($boardUser->id)
        ->and(ProjectBoardVote::query()->where('legacy_id', 99)->firstOrFail()->board_type)->toBe(BoardType::At)
        ->and(ProjectBoardVote::query()->where('legacy_id', 100)->firstOrFail()->board_type)->toBe(BoardType::Ot)
        ->and(BoardVoteRejection::query()->where('legacy_id', 101)->firstOrFail()->comment)->toBe('Powód odrzucenia')
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
