<?php

use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Files\Models\ProjectFile;
use App\Domain\Projects\Actions\ApplyCorrectionAction;
use App\Domain\Projects\Actions\DecideProjectChangeSuggestionAction;
use App\Domain\Projects\Actions\StartCorrectionAction;
use App\Domain\Projects\Enums\ProjectChangeSuggestionDecision;
use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Models\ProjectChangeSuggestion;
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

it('accepts a project change suggestion like legacy and applies new project data', function (): void {
    $actor = User::factory()->create();
    $project = submittedProjectReadyForCorrection($actor);
    $newArea = ProjectArea::query()->create(areaAttributes([
        'legacy_id' => 200,
        'name' => 'Nowy obszar',
    ]));
    $file = ProjectFile::query()->create([
        'legacy_id' => 300,
        'project_id' => $project->id,
        'stored_name' => 'map.pdf',
        'original_name' => 'map.pdf',
        'description' => 'Stary opis',
        'type' => ProjectFileType::Map,
    ]);

    $suggestion = ProjectChangeSuggestion::query()->create([
        'project_id' => $project->id,
        'created_by_id' => $actor->id,
        'old_data' => ['title' => $project->title],
        'old_costs' => [['description' => 'Stary koszt', 'sum' => '1000']],
        'old_files' => [['id' => $file->legacy_id, 'description' => 'Stary opis']],
        'new_data' => [
            'title' => 'Tytuł po propozycji',
            'taskTypeId' => $newArea->legacy_id,
            'description' => 'Opis po propozycji',
            'goal' => 'Cel po propozycji',
            'mapData' => [],
        ],
        'new_costs' => [['description' => 'Nowy koszt', 'sum' => '2500']],
        'new_files' => [['id' => $file->legacy_id, 'description' => 'Nowy opis']],
        'deadline' => now()->addDay(),
    ]);

    $decided = app(DecideProjectChangeSuggestionAction::class)->execute(
        $suggestion,
        ProjectChangeSuggestionDecision::Accepted,
        $actor,
    );

    $project->refresh();

    expect($decided->decision)->toBe(ProjectChangeSuggestionDecision::Accepted)
        ->and($project->title)->toBe('Tytuł po propozycji')
        ->and($project->project_area_id)->toBe($newArea->id)
        ->and($project->description)->toBe('Opis po propozycji')
        ->and($project->status)->toBe(ProjectStatus::ChangesSuggestionAccepted)
        ->and($project->costItems()->firstOrFail()->amount)->toBe('2500.00')
        ->and($file->refresh()->description)->toBe('Nowy opis');
});

it('declines a project change suggestion and returns project to merit verification', function (): void {
    $actor = User::factory()->create();
    $project = submittedProjectReadyForCorrection($actor);
    $project->forceFill([
        'status' => ProjectStatus::DuringChangesSuggestion,
    ])->save();

    $suggestion = ProjectChangeSuggestion::query()->create([
        'project_id' => $project->id,
        'created_by_id' => $actor->id,
        'old_data' => [],
        'old_costs' => [],
        'old_files' => [],
        'new_data' => [],
        'new_costs' => [],
        'new_files' => [],
        'deadline' => now()->addDay(),
    ]);

    app(DecideProjectChangeSuggestionAction::class)->execute(
        $suggestion,
        ProjectChangeSuggestionDecision::Declined,
        $actor,
    );

    expect($suggestion->refresh()->decision)->toBe(ProjectChangeSuggestionDecision::Declined)
        ->and($project->refresh()->status)->toBe(ProjectStatus::DuringMeritVerification);
});
