<?php

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemRole;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Actions\AssignVerificationDepartmentAction;
use App\Domain\Verification\Actions\SubmitInitialMeritVerificationAction;
use App\Domain\Verification\Enums\VerificationAssignmentType;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\User;

function verificationOverviewProject(ProjectStatus $status = ProjectStatus::DuringInitialVerification): Project
{
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());

    return project($edition->id, $area->id, [
        'status' => $status,
        'is_support_list' => true,
    ]);
}

it('shows verification overview only to project verifiers', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $applicant = User::factory()->create();
    $applicant->assignRole(SystemRole::Applicant->value);
    $project = verificationOverviewProject();

    $this->actingAs($coordinator);
    expect(ProjectResource::canViewVerificationOverview($project))->toBeTrue();

    $this->actingAs($applicant);
    expect(ProjectResource::canViewVerificationOverview($project))->toBeFalse();
});

it('builds verification overview form data with assignments cards and versions', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create(['name' => 'Koordynator']);
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $project = verificationOverviewProject(ProjectStatus::DuringInitialVerification);
    $department = Department::query()->create(['name' => 'Wydział Zieleni']);

    app(AssignVerificationDepartmentAction::class)->execute($project, $department, VerificationAssignmentType::MeritInitial);
    app(SubmitInitialMeritVerificationAction::class)->execute(
        $project,
        $department,
        $coordinator,
        true,
        ['notes' => 'Pozytywnie'],
    );

    $data = ProjectResource::verificationOverviewFormData($project->refresh());

    expect($data['verification_overview'])
        ->toContain('Weryfikacja wstępna')
        ->toContain('Wydział Zieleni')
        ->toContain('wynik pozytywny')
        ->and($data['verification_versions'])
        ->toContain('Weryfikacja wstępna')
        ->toContain('operator: Koordynator');
});
