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
use App\Domain\Verification\Enums\VerificationCardStatus;
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
        'citizen_dialog_office_question_1' => 1,
        'citizen_dialog_office_question_1_comments' => 'Nie jest samą dokumentacją.',
        'mayor_office_recommendation' => 'Jednostka wiodąca: WIM.',
        'mayor_office_recommendation_comments' => 'Rekomendacja z legacy.',
        'property_office_suboffice1_property_owner_skip' => 2,
    ]);

    expect($verification->result)->toBeTrue()
        ->and($verification->answers)->toMatchArray([
            'notes' => 'Brak uwag.',
            'citizenDialogOfficeQuestion1' => 1,
            'citizenDialogOfficeQuestion1Comments' => 'Nie jest samą dokumentacją.',
            'mayorOfficeRecommendation' => 'Jednostka wiodąca: WIM.',
            'mayorOfficeRecommendationComments' => 'Rekomendacja z legacy.',
            'propertyOfficeSuboffice1PropertyOwnerSkip' => 2,
        ])
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
        'is_law_compliant' => 1,
        'is_law_compliant_comments' => 'Zgodny z zakresem jednostki.',
        'has_additional_costs' => 1,
        'additional_information' => 'Może trafić pod głosowanie.',
        'corrected_costs' => [
            ['description' => 'Prace budowlane', 'sum' => '1500.50'],
            ['description' => 'Nadzór', 'sum' => 300],
        ],
        'future_costs' => [
            ['description' => 'Utrzymanie', 'sum' => 250],
            ['description' => '', 'sum' => ''],
        ],
    ]);

    expect($verification->result)->toBeTrue()
        ->and($verification->answers)->toMatchArray([
            'notes' => 'Pozytywna ocena.',
            'isLawCompliant' => 1,
            'isLawCompliantComments' => 'Zgodny z zakresem jednostki.',
            'hasAdditionalCosts' => 1,
            'additionalInformation' => 'Może trafić pod głosowanie.',
        ])
        ->and($verification->answers['correctedCost'])->toBe([
            ['description' => 'Prace budowlane', 'sum' => 1500.5],
            ['description' => 'Nadzór', 'sum' => 300],
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
        'consultation_information' => 'Brak kolizji z planami jednostki.',
        'answers_notes' => 'Bez uwag.',
    ]);

    expect($verification->result)->toBeTrue()
        ->and($verification->answers)->toBe([
            'notes' => 'Bez uwag.',
            'consultationInformation' => 'Brak kolizji z planami jednostki.',
        ])
        ->and($project->refresh()->status)->toBe(ProjectStatus::DuringMeritVerification);
});

it('returns sent verification card from filament form through domain action', function (): void {
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
    ]);

    expect(ProjectResource::canReturnVerificationCard($project->refresh()))->toBeTrue();

    $returned = ProjectResource::returnVerificationCardFromAdminForm($project, [
        'type' => VerificationAssignmentType::MeritInitial->value,
        'department_id' => $department->id,
    ]);

    $assignment = $project->verificationAssignments()
        ->where('department_id', $department->id)
        ->where('type', VerificationAssignmentType::MeritInitial->value)
        ->firstOrFail();

    expect($returned->id)->toBe($verification->id)
        ->and($returned->status)->toBe(VerificationCardStatus::WorkingCopy)
        ->and($assignment->is_returned)->toBeTrue()
        ->and($assignment->sent_at)->toBeNull()
        ->and(VerificationVersion::query()
            ->where('verification_legacy_id', $verification->id)
            ->where('type', VerificationAssignmentType::MeritInitial->value)
            ->count())->toBe(2);
});
