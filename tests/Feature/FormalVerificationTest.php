<?php

use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Actions\BeginFormalVerificationAction;
use App\Domain\Verification\Actions\CompleteFormalVerificationAction;
use App\Domain\Verification\Actions\ForwardFormalVerificationToInitialVerificationAction;
use App\Domain\Verification\Actions\RequestFormalCorrectionAction;
use App\Domain\Verification\Enums\VerificationAssignmentType;
use App\Models\User;

function submittedProjectForFormalVerification(array $overrides = []): Project
{
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());

    return project($edition->id, $area->id, [
        'status' => ProjectStatus::Submitted,
        'is_support_list' => true,
        ...$overrides,
    ]);
}

it('moves submitted project into formal verification', function (): void {
    $actor = User::factory()->create();
    $project = submittedProjectForFormalVerification();

    $updated = app(BeginFormalVerificationAction::class)->execute($project, $actor);

    expect($updated->status)->toBe(ProjectStatus::DuringFormalVerification);
});

it('completes positive formal verification and updates project status', function (): void {
    $actor = User::factory()->create();
    $project = submittedProjectForFormalVerification([
        'status' => ProjectStatus::DuringFormalVerification,
    ]);

    $verification = app(CompleteFormalVerificationAction::class)->execute($project, $actor, true, [
        'wasSentOnCorrectForm' => 1,
        'hasSupportAttachment' => 1,
    ]);

    expect($verification->result)->toBeTrue()
        ->and($verification->status)->toBe(ProjectStatus::FormallyVerified->value)
        ->and($project->refresh()->status)->toBe(ProjectStatus::FormallyVerified);
});

it('rejects positive formal verification without support list confirmation', function (): void {
    $actor = User::factory()->create();
    $project = submittedProjectForFormalVerification([
        'status' => ProjectStatus::DuringFormalVerification,
        'is_support_list' => false,
    ]);

    app(CompleteFormalVerificationAction::class)->execute($project, $actor, true);
})->throws(DomainException::class, 'Pozytywna weryfikacja formalna wymaga poprawnej listy poparcia.');

it('requires a reason for negative formal verification', function (): void {
    $actor = User::factory()->create();
    $project = submittedProjectForFormalVerification([
        'status' => ProjectStatus::DuringFormalVerification,
    ]);

    app(CompleteFormalVerificationAction::class)->execute($project, $actor, false);
})->throws(DomainException::class, 'Negatywna weryfikacja formalna wymaga uzasadnienia.');

it('completes negative formal verification with rejection status', function (): void {
    $actor = User::factory()->create();
    $project = submittedProjectForFormalVerification([
        'status' => ProjectStatus::DuringFormalVerification,
    ]);

    $verification = app(CompleteFormalVerificationAction::class)->execute(
        $project,
        $actor,
        false,
        resultComments: 'Brak wymaganych danych.',
    );

    expect($verification->result)->toBeFalse()
        ->and($verification->status)->toBe(ProjectStatus::RejectedFormally->value)
        ->and($project->refresh()->status)->toBe(ProjectStatus::RejectedFormally);
});

it('forwards formally verified project to initial merit verification departments', function (): void {
    $actor = User::factory()->create();
    $project = submittedProjectForFormalVerification([
        'status' => ProjectStatus::FormallyVerified,
    ]);
    $firstDepartment = Department::query()->create(['name' => 'Wydział Inwestycji']);
    $secondDepartment = Department::query()->create(['name' => 'Wydział Zieleni']);

    $updated = app(ForwardFormalVerificationToInitialVerificationAction::class)->execute(
        $project,
        $actor,
        [$firstDepartment, $secondDepartment],
        notes: 'Do weryfikacji wstępnej',
    );

    expect($updated->status)->toBe(ProjectStatus::DuringInitialVerification)
        ->and($updated->need_pre_verification)->toBeTrue()
        ->and($project->verificationAssignments()
            ->where('type', VerificationAssignmentType::MeritInitial->value)
            ->count())->toBe(2);
});

it('requires department when forwarding formal verification to initial verification', function (): void {
    $actor = User::factory()->create();
    $project = submittedProjectForFormalVerification([
        'status' => ProjectStatus::FormallyVerified,
    ]);

    app(ForwardFormalVerificationToInitialVerificationAction::class)->execute($project, $actor, []);
})->throws(DomainException::class, 'Przekazanie do weryfikacji wstępnej wymaga co najmniej jednej jednostki.');

it('starts formal correction and keeps project in formal verification', function (): void {
    $actor = User::factory()->create();
    $project = submittedProjectForFormalVerification();

    $correction = app(RequestFormalCorrectionAction::class)->execute(
        $project,
        $actor,
        [ProjectCorrectionField::Description, ProjectCorrectionField::SupportAttachment],
        'Uzupełnić opis i listę poparcia.',
    );

    expect($correction->allowed_fields)->toBe([
        ProjectCorrectionField::Description->value,
        ProjectCorrectionField::SupportAttachment->value,
    ])
        ->and($project->refresh()->status)->toBe(ProjectStatus::DuringFormalVerification)
        ->and($project->need_correction)->toBeTrue()
        ->and($project->correction_no)->toBe(1);
});

it('rejects formal correction for projects outside formal flow', function (): void {
    $actor = User::factory()->create();
    $project = submittedProjectForFormalVerification([
        'status' => ProjectStatus::FormallyVerified,
    ]);

    app(RequestFormalCorrectionAction::class)->execute($project, $actor, [ProjectCorrectionField::Description]);
})->throws(DomainException::class, 'Korektę formalną można uruchomić tylko dla projektu złożonego albo w weryfikacji formalnej.');
