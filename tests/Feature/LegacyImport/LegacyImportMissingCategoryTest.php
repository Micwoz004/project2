<?php

use App\Domain\Files\Models\ProjectFile;
use App\Domain\LegacyImport\Services\LegacyFixtureImportService;
use App\Domain\Projects\Models\Project;
use App\Domain\Verification\Models\FinalMeritVerification;
use App\Domain\Verification\Models\ProjectBoardVote;

it('imports legacy projects with empty category and area ids as nullable relations', function (): void {
    app(LegacyFixtureImportService::class)->import([
        'taskgroups' => [[
            'id' => 10,
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
        ]],
        'tasks' => [[
            'id' => 40,
            'taskGroupId' => 10,
            'taskTypeId' => 20,
            'categoryId' => null,
            'title' => 'Projekt bez kategorii historycznej',
            'status' => 1,
        ], [
            'id' => 41,
            'taskGroupId' => 10,
            'taskTypeId' => 0,
            'categoryId' => 0,
            'title' => 'Projekt bez obszaru historycznego',
            'status' => 1,
        ]],
        'taskscategories' => [[
            'taskId' => 40,
            'categoryId' => 0,
        ]],
        'files' => [[
            'id' => 50,
            'taskId' => 40,
            'fileName' => 'legacy-public-file.pdf',
            'originalName' => 'Publiczny plik.pdf',
            'type' => 3,
        ]],
    ], 'missing-category-fixture');

    $project = Project::query()->where('legacy_id', 40)->firstOrFail();
    $projectWithoutArea = Project::query()->where('legacy_id', 41)->firstOrFail();
    $file = ProjectFile::query()->where('legacy_id', 50)->firstOrFail();

    expect($project->category_id)->toBeNull()
        ->and($project->categories()->count())->toBe(0)
        ->and($projectWithoutArea->project_area_id)->toBeNull()
        ->and($projectWithoutArea->category_id)->toBeNull()
        ->and($file->stored_name)->toBe('legacy-public-file.pdf');
});

it('skips legacy verification rows that point to deleted projects', function (): void {
    app(LegacyFixtureImportService::class)->import([
        'taskgroups' => [[
            'id' => 10,
            'proposeStart' => '2025-01-01 00:00:00',
            'proposeEnd' => '2025-02-01 00:00:00',
            'preVotingVerificationEnd' => '2025-03-01 00:00:00',
            'votingStart' => '2025-04-01 00:00:00',
            'votingEnd' => '2025-04-15 23:59:59',
            'postVotingVerificationEnd' => '2025-05-01 00:00:00',
            'resultAnnouncementEnd' => '2025-06-01 00:00:00',
        ]],
        'tasks' => [[
            'id' => 40,
            'taskGroupId' => 10,
            'taskTypeId' => 0,
            'categoryId' => null,
            'title' => 'Projekt z weryfikacja',
            'status' => 1,
        ]],
        'taskfinishmeritverification' => [[
            'id' => 50,
            'taskId' => 40,
            'status' => 1,
        ], [
            'id' => 51,
            'taskId' => 1078,
            'status' => 1,
        ]],
    ], 'missing-project-verification-fixture');

    expect(FinalMeritVerification::query()->where('legacy_id', 50)->exists())->toBeTrue()
        ->and(FinalMeritVerification::query()->where('legacy_id', 51)->exists())->toBeFalse();
});

it('skips legacy board votes that point to deleted users', function (): void {
    app(LegacyFixtureImportService::class)->import([
        'taskgroups' => [[
            'id' => 10,
            'proposeStart' => '2025-01-01 00:00:00',
            'proposeEnd' => '2025-02-01 00:00:00',
            'preVotingVerificationEnd' => '2025-03-01 00:00:00',
            'votingStart' => '2025-04-01 00:00:00',
            'votingEnd' => '2025-04-15 23:59:59',
            'postVotingVerificationEnd' => '2025-05-01 00:00:00',
            'resultAnnouncementEnd' => '2025-06-01 00:00:00',
        ]],
        'tasks' => [[
            'id' => 40,
            'taskGroupId' => 10,
            'taskTypeId' => 0,
            'categoryId' => null,
            'title' => 'Projekt z glosowaniem komisji',
            'status' => 1,
        ]],
        'otvotes' => [[
            'id' => 50,
            'taskId' => 40,
            'userId' => 4837,
            'vote' => 1,
        ]],
    ], 'missing-user-board-vote-fixture');

    expect(ProjectBoardVote::query()->where('legacy_id', 50)->exists())->toBeFalse();
});
