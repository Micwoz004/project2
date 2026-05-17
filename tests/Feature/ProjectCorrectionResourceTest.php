<?php

use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Files\Models\ProjectFile;
use App\Domain\Projects\Enums\ProjectChangeSuggestionDecision;
use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Models\ProjectChangeSuggestion;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Domain\Users\Enums\SystemRole;
use App\Filament\Resources\ProjectChangeSuggestions\ProjectChangeSuggestionResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\User;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

function correctionResourceProject(User $creator, ProjectStatus $status = ProjectStatus::Submitted): Project
{
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = project($edition->id, $area->id, [
        'creator_id' => $creator->id,
        'status' => $status,
        'is_support_list' => true,
        'submitted_at' => now()->subDay(),
    ]);

    $project->costItems()->create([
        'description' => 'Koszt bazowy',
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

it('shows correction actions only to project managers for matching project state', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $submitted = correctionResourceProject($coordinator);
    $draft = correctionResourceProject($coordinator, ProjectStatus::WorkingCopy);
    $activeCorrection = correctionResourceProject($coordinator);
    $activeCorrection->forceFill([
        'need_correction' => true,
        'correction_start_time' => now()->subHour(),
        'correction_end_time' => now()->addDay(),
    ])->save();

    expect(ProjectResource::canStartProjectCorrection($submitted))->toBeTrue()
        ->and(ProjectResource::canStartProjectCorrection($draft))->toBeFalse()
        ->and(ProjectResource::canApplyProjectCorrection($activeCorrection))->toBeTrue();

    $applicant = User::factory()->create();
    $applicant->assignRole(SystemRole::Applicant->value);
    $this->actingAs($applicant);

    expect(ProjectResource::canStartProjectCorrection($submitted))->toBeFalse()
        ->and(ProjectResource::canApplyProjectCorrection($activeCorrection))->toBeFalse();
});

it('allows correction actions through granular permission', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $operator = User::factory()->create();
    $operator->givePermissionTo(SystemPermission::ProjectCorrectionsManage->value);
    $this->actingAs($operator);

    $submitted = correctionResourceProject($operator);
    $activeCorrection = correctionResourceProject($operator);
    $activeCorrection->forceFill([
        'need_correction' => true,
        'correction_start_time' => now()->subHour(),
        'correction_end_time' => now()->addDay(),
    ])->save();

    expect(ProjectResource::canStartProjectCorrection($submitted))->toBeTrue()
        ->and(ProjectResource::canApplyProjectCorrection($activeCorrection))->toBeTrue();
});

it('lists and decides project change suggestions through filament resource', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $operator = User::factory()->create(['status' => true]);
    $operator->givePermissionTo(SystemPermission::AdminAccess->value);
    $operator->givePermissionTo(SystemPermission::ProjectCorrectionsManage->value);
    $this->actingAs($operator);

    $project = correctionResourceProject($operator, ProjectStatus::DuringChangesSuggestion);
    $suggestion = ProjectChangeSuggestion::query()->create([
        'project_id' => $project->id,
        'created_by_id' => $operator->id,
        'old_data' => [],
        'old_costs' => [],
        'old_files' => [],
        'new_data' => [],
        'new_costs' => [],
        'new_files' => [],
        'deadline' => now()->addDay(),
    ]);

    $this->get(ProjectChangeSuggestionResource::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee($project->title)
        ->assertSee('oczekuje');

    $decided = ProjectChangeSuggestionResource::declineFromAdmin($suggestion);

    expect($decided->decision)->toBe(ProjectChangeSuggestionDecision::Declined)
        ->and($decided->decision_by_id)->toBe($operator->id)
        ->and($project->refresh()->status)->toBe(ProjectStatus::DuringMeritVerification)
        ->and(array_keys(ProjectChangeSuggestionResource::getPages()))->toBe(['index'])
        ->and(ProjectChangeSuggestionResource::canCreate())->toBeFalse();
});

it('starts project correction from filament form through domain action', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();
    Carbon::setTestNow('2026-05-16 10:00:00');

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $project = correctionResourceProject($coordinator);

    $correction = ProjectResource::startProjectCorrectionFromAdminForm($project, [
        'allowed_fields' => [
            ProjectCorrectionField::Title->value,
            ProjectCorrectionField::Goal->value,
        ],
        'notes' => 'Poprawić tytuł i cel.',
        'deadline' => '2026-05-20 12:00:00',
    ]);

    expect($correction->allowed_fields)->toBe([
        ProjectCorrectionField::Title->value,
        ProjectCorrectionField::Goal->value,
    ])
        ->and($project->refresh()->need_correction)->toBeTrue()
        ->and($project->correction_no)->toBe(1)
        ->and($project->correction_end_time->toDateTimeString())->toBe('2026-05-20 12:00:00');
});

it('applies project correction from filament form and records version', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();
    Carbon::setTestNow('2026-05-16 10:00:00');

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $project = correctionResourceProject($coordinator);

    ProjectResource::startProjectCorrectionFromAdminForm($project, [
        'allowed_fields' => [ProjectCorrectionField::Title->value],
        'deadline' => now()->addDays(3)->toDateTimeString(),
    ]);

    $updated = ProjectResource::applyProjectCorrectionFromAdminForm($project->refresh(), [
        'title' => 'Poprawiony tytuł',
        'description' => 'Opis niedozwolony w tej korekcie',
    ]);

    expect($updated->title)->toBe('Poprawiony tytuł')
        ->and($updated->description)->toBe('Opis projektu')
        ->and($updated->need_correction)->toBeFalse()
        ->and($updated->versions()->count())->toBe(1)
        ->and($project->corrections()->latest()->firstOrFail()->correction_done)->toBeTrue();
});
