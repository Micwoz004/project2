<?php

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Domain\Users\Enums\SystemRole;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Actions\AssignVerificationDepartmentAction;
use App\Domain\Verification\Enums\VerificationAssignmentType;
use App\Domain\Verification\Models\VerificationVersion;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\User;

function meritResourceProject(ProjectStatus $status): Project
{
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());

    return project($edition->id, $area->id, [
        'status' => $status,
        'is_support_list' => true,
    ]);
}

function meritResourceDepartment(string $name = 'Wydział Merytoryczny'): Department
{
    return Department::query()->create(['name' => $name]);
}

it('shows merit verification actions only to project verifiers for matching statuses', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $formallyVerified = meritResourceProject(ProjectStatus::FormallyVerified);
    $duringInitial = meritResourceProject(ProjectStatus::DuringInitialVerification);
    $sentForMerit = meritResourceProject(ProjectStatus::SentForMeritVerification);
    $duringMerit = meritResourceProject(ProjectStatus::DuringMeritVerification);
    $picked = meritResourceProject(ProjectStatus::Picked);

    expect(ProjectResource::canAssignMeritDepartments($formallyVerified))->toBeTrue()
        ->and(ProjectResource::canAssignMeritDepartments($sentForMerit))->toBeTrue()
        ->and(ProjectResource::canSubmitInitialMeritVerification($formallyVerified))->toBeTrue()
        ->and(ProjectResource::canSubmitInitialMeritVerification($duringInitial))->toBeTrue()
        ->and(ProjectResource::canSubmitInitialMeritVerification($sentForMerit))->toBeFalse()
        ->and(ProjectResource::canSubmitFinalMeritVerification($sentForMerit))->toBeTrue()
        ->and(ProjectResource::canSubmitFinalMeritVerification($duringMerit))->toBeTrue()
        ->and(ProjectResource::canSubmitConsultationVerification($duringMerit))->toBeTrue()
        ->and(ProjectResource::canAssignMeritDepartments($picked))->toBeFalse();

    $applicant = User::factory()->create();
    $applicant->assignRole(SystemRole::Applicant->value);
    $this->actingAs($applicant);

    expect(ProjectResource::canAssignMeritDepartments($formallyVerified))->toBeFalse()
        ->and(ProjectResource::canSubmitInitialMeritVerification($formallyVerified))->toBeFalse()
        ->and(ProjectResource::canSubmitFinalMeritVerification($sentForMerit))->toBeFalse()
        ->and(ProjectResource::canSubmitConsultationVerification($duringMerit))->toBeFalse();
});

it('allows merit verification actions through granular permission', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $meritVerifier = User::factory()->create();
    $meritVerifier->givePermissionTo(SystemPermission::MeritVerificationManage->value);
    $this->actingAs($meritVerifier);

    $formallyVerified = meritResourceProject(ProjectStatus::FormallyVerified);

    expect(ProjectResource::canAssignMeritDepartments($formallyVerified))->toBeTrue();

    $formalOnly = User::factory()->create();
    $formalOnly->givePermissionTo(SystemPermission::FormalVerificationManage->value);
    $this->actingAs($formalOnly);

    expect(ProjectResource::canAssignMeritDepartments($formallyVerified))->toBeFalse();
});

it('assigns merit departments from filament form through domain action', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $project = meritResourceProject(ProjectStatus::SentForMeritVerification);
    $firstDepartment = meritResourceDepartment('Wydział Inwestycji');
    $secondDepartment = meritResourceDepartment('Wydział Zieleni');

    $assignments = ProjectResource::assignMeritDepartmentsFromAdminForm($project, [
        'type' => VerificationAssignmentType::MeritFinish->value,
        'department_ids' => [$firstDepartment->id, $secondDepartment->id],
        'notes' => 'Końcowa ocena merytoryczna.',
    ]);

    expect($assignments)->toHaveCount(2)
        ->and($project->verificationAssignments()
            ->where('type', VerificationAssignmentType::MeritFinish->value)
            ->count())->toBe(2);
});

it('submits initial merit verification from filament form through domain action', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $project = meritResourceProject(ProjectStatus::FormallyVerified);
    $department = meritResourceDepartment();
    app(AssignVerificationDepartmentAction::class)->execute($project, $department, VerificationAssignmentType::MeritInitial);

    $verification = ProjectResource::submitInitialMeritVerificationFromAdminForm($project, [
        'department_id' => $department->id,
        'result' => true,
        'answers_notes' => 'Brak uwag.',
    ]);

    expect($verification->result)->toBeTrue()
        ->and($verification->answers)->toBe(['notes' => 'Brak uwag.'])
        ->and($project->refresh()->status)->toBe(ProjectStatus::SentForMeritVerification)
        ->and(VerificationVersion::query()
            ->where('verification_legacy_id', $verification->id)
            ->where('type', VerificationAssignmentType::MeritInitial->value)
            ->count())->toBe(1);
});

it('submits final merit verification with costs from filament form through domain action', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $project = meritResourceProject(ProjectStatus::SentForMeritVerification);
    $department = meritResourceDepartment();
    app(AssignVerificationDepartmentAction::class)->execute($project, $department, VerificationAssignmentType::MeritFinish);

    $verification = ProjectResource::submitFinalMeritVerificationFromAdminForm($project, [
        'department_id' => $department->id,
        'result' => true,
        'answers_notes' => 'Pozytywna ocena.',
        'corrected_cost_description' => 'Prace budowlane',
        'corrected_cost_sum' => '1500.50',
        'future_cost_description' => 'Utrzymanie',
        'future_cost_sum' => 250,
    ]);

    expect($verification->result)->toBeTrue()
        ->and($verification->answers['correctedCost'])->toBe([
            ['description' => 'Prace budowlane', 'sum' => 1500.5],
        ])
        ->and($verification->answers['futureCost'])->toBe([
            ['description' => 'Utrzymanie', 'sum' => 250],
        ])
        ->and($project->refresh()->status)->toBe(ProjectStatus::MeritVerificationAccepted);
});

it('submits consultation from filament form without changing project status', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $project = meritResourceProject(ProjectStatus::DuringMeritVerification);
    $department = meritResourceDepartment('Jednostka konsultująca');
    app(AssignVerificationDepartmentAction::class)->execute($project, $department, VerificationAssignmentType::Consultation);

    $verification = ProjectResource::submitConsultationVerificationFromAdminForm($project, [
        'department_id' => $department->id,
        'result' => true,
        'answers_notes' => 'Bez uwag.',
    ]);

    expect($verification->result)->toBeTrue()
        ->and($verification->answers)->toBe(['notes' => 'Bez uwag.'])
        ->and($project->refresh()->status)->toBe(ProjectStatus::DuringMeritVerification);
});
