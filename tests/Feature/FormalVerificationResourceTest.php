<?php

use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Domain\Users\Enums\SystemRole;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Enums\VerificationAssignmentType;
use App\Domain\Verification\Models\VerificationVersion;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\User;

function formalResourceProject(ProjectStatus $status, array $overrides = []): Project
{
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());

    return project($edition->id, $area->id, [
        'status' => $status,
        'is_support_list' => true,
        ...$overrides,
    ]);
}

it('shows formal verification actions only to project verifiers for matching statuses', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $submitted = formalResourceProject(ProjectStatus::Submitted);
    $duringFormal = formalResourceProject(ProjectStatus::DuringFormalVerification);
    $formallyVerified = formalResourceProject(ProjectStatus::FormallyVerified);
    $picked = formalResourceProject(ProjectStatus::Picked);

    expect(ProjectResource::canBeginFormalVerification($submitted))->toBeTrue()
        ->and(ProjectResource::canBeginFormalVerification($duringFormal))->toBeFalse()
        ->and(ProjectResource::canCompleteFormalVerification($submitted))->toBeTrue()
        ->and(ProjectResource::canCompleteFormalVerification($duringFormal))->toBeTrue()
        ->and(ProjectResource::canRequestFormalCorrection($duringFormal))->toBeTrue()
        ->and(ProjectResource::canForwardFormalVerification($formallyVerified))->toBeTrue()
        ->and(ProjectResource::canForwardFormalVerification($picked))->toBeFalse();

    $applicant = User::factory()->create();
    $applicant->assignRole(SystemRole::Applicant->value);
    $this->actingAs($applicant);

    expect(ProjectResource::canBeginFormalVerification($submitted))->toBeFalse()
        ->and(ProjectResource::canCompleteFormalVerification($duringFormal))->toBeFalse()
        ->and(ProjectResource::canRequestFormalCorrection($duringFormal))->toBeFalse()
        ->and(ProjectResource::canForwardFormalVerification($formallyVerified))->toBeFalse();
});

it('allows formal verification actions through granular permission', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $formalVerifier = User::factory()->create();
    $formalVerifier->givePermissionTo(SystemPermission::FormalVerificationManage->value);
    $this->actingAs($formalVerifier);

    $submitted = formalResourceProject(ProjectStatus::Submitted);

    expect(ProjectResource::canBeginFormalVerification($submitted))->toBeTrue();

    $meritOnly = User::factory()->create();
    $meritOnly->givePermissionTo(SystemPermission::MeritVerificationManage->value);
    $this->actingAs($meritOnly);

    expect(ProjectResource::canBeginFormalVerification($submitted))->toBeFalse();
});

it('completes positive formal verification from filament form through domain action', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $project = formalResourceProject(ProjectStatus::DuringFormalVerification);

    $verification = ProjectResource::completeFormalVerificationFromAdminForm($project, [
        'was_sent_on_correct_form' => true,
        'has_support_attachment' => true,
    ], true);

    expect($verification->result)->toBeTrue()
        ->and($verification->answers)->toBe([
            'wasSentOnCorrectForm' => 1,
            'hasSupportAttachment' => 1,
        ])
        ->and($project->refresh()->status)->toBe(ProjectStatus::FormallyVerified)
        ->and(VerificationVersion::query()->where('verification_legacy_id', $verification->id)->count())->toBe(1);
});

it('requests formal correction from filament form through domain action', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $project = formalResourceProject(ProjectStatus::DuringFormalVerification);

    $correction = ProjectResource::requestFormalCorrectionFromAdminForm($project, [
        'allowed_fields' => [
            ProjectCorrectionField::Description->value,
            ProjectCorrectionField::SupportAttachment->value,
        ],
        'notes' => 'Uzupełnić opis i listę poparcia.',
    ]);

    expect($correction->allowed_fields)->toBe([
        ProjectCorrectionField::Description->value,
        ProjectCorrectionField::SupportAttachment->value,
    ])
        ->and($project->refresh()->status)->toBe(ProjectStatus::DuringFormalVerification)
        ->and($project->need_correction)->toBeTrue();
});

it('forwards formally verified project from filament form to initial merit departments', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $project = formalResourceProject(ProjectStatus::FormallyVerified);
    $firstDepartment = Department::query()->create(['name' => 'Wydział Inwestycji']);
    $secondDepartment = Department::query()->create(['name' => 'Wydział Zieleni']);

    $updated = ProjectResource::forwardFormalVerificationFromAdminForm($project, [
        'department_ids' => [$firstDepartment->id, $secondDepartment->id],
        'notes' => 'Do opinii wstępnej.',
    ]);

    expect($updated->status)->toBe(ProjectStatus::DuringInitialVerification)
        ->and($updated->need_pre_verification)->toBeTrue()
        ->and($project->verificationAssignments()
            ->where('type', VerificationAssignmentType::MeritInitial->value)
            ->count())->toBe(2);
});
