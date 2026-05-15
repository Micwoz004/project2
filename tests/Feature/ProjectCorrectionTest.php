<?php

use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Files\Models\ProjectFile;
use App\Domain\Projects\Actions\ApplyCorrectionAction;
use App\Domain\Projects\Actions\StartCorrectionAction;
use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Models\User;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

function submittedProjectReadyForCorrection(User $user): Project
{
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'creator_id' => $user->id,
        'status' => ProjectStatus::Submitted,
        'is_support_list' => true,
        'submitted_at' => now()->subDay(),
    ]));

    $project->costItems()->create([
        'description' => 'Prace projektowe',
        'amount' => 1000,
    ]);

    ProjectFile::query()->create([
        'project_id' => $project->id,
        'stored_name' => 'support.pdf',
        'original_name' => 'support.pdf',
        'type' => ProjectFileType::SupportList,
    ]);

    return $project;
}

it('starts a correction window and increments correction number', function (): void {
    Carbon::setTestNow('2026-05-15 10:00:00');

    $actor = User::factory()->create();
    $project = submittedProjectReadyForCorrection($actor);

    $correction = app(StartCorrectionAction::class)->execute(
        $project,
        $actor,
        [ProjectCorrectionField::Title, ProjectCorrectionField::Goal],
        'Popraw tytuł i cel.',
        now()->addDays(3),
    );

    $project->refresh();

    expect($correction->allowed_fields)->toBe([
        ProjectCorrectionField::Title->value,
        ProjectCorrectionField::Goal->value,
    ])
        ->and($project->need_correction)->toBeTrue()
        ->and($project->correction_no)->toBe(1)
        ->and($project->correction_start_time->toDateTimeString())->toBe('2026-05-15 10:00:00')
        ->and($project->correction_end_time->toDateTimeString())->toBe(now()->addDays(3)->toDateTimeString());
});

it('applies only fields allowed by active correction and records a version', function (): void {
    Carbon::setTestNow('2026-05-15 10:00:00');

    $actor = User::factory()->create();
    $project = submittedProjectReadyForCorrection($actor);

    $correction = app(StartCorrectionAction::class)->execute(
        $project,
        $actor,
        [ProjectCorrectionField::Title],
        deadline: now()->addDays(3),
    );

    $updated = app(ApplyCorrectionAction::class)->execute($project->refresh(), $actor, [
        'title' => 'Poprawiony tytuł',
        'description' => 'Tego pola nie wolno poprawić',
    ]);

    expect($updated->title)->toBe('Poprawiony tytuł')
        ->and($updated->description)->toBe('Opis projektu')
        ->and($updated->need_correction)->toBeFalse()
        ->and($updated->versions()->count())->toBe(1)
        ->and($correction->refresh()->correction_done)->toBeTrue();
});

it('rejects corrections outside an active window', function (): void {
    Carbon::setTestNow('2026-05-15 10:00:00');

    $actor = User::factory()->create();
    $project = submittedProjectReadyForCorrection($actor);

    app(StartCorrectionAction::class)->execute(
        $project,
        $actor,
        [ProjectCorrectionField::Title],
        deadline: now()->subMinute(),
    );

    app(ApplyCorrectionAction::class)->execute($project->refresh(), $actor, [
        'title' => 'Spóźniona korekta',
    ]);
})->throws(DomainException::class, 'Projekt nie jest w aktywnym oknie korekty.');
