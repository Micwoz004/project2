<?php

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Actions\AssignVerificationDepartmentAction;
use App\Domain\Verification\Actions\ReturnVerificationCardAction;
use App\Domain\Verification\Actions\SubmitConsultationVerificationAction;
use App\Domain\Verification\Actions\SubmitFinalMeritVerificationAction;
use App\Domain\Verification\Actions\SubmitInitialMeritVerificationAction;
use App\Domain\Verification\Enums\VerificationAssignmentType;
use App\Domain\Verification\Enums\VerificationCardStatus;
use App\Domain\Verification\Models\VerificationVersion;
use App\Models\User;

function meritProject(ProjectStatus $status): Project
{
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());

    return project($edition->id, $area->id, [
        'status' => $status,
        'is_support_list' => true,
    ]);
}

function verificationDepartment(string $name = 'Wydział Merytoryczny'): Department
{
    return Department::query()->create([
        'name' => $name,
    ]);
}

it('assigns a department to a merit verification type', function (): void {
    $project = meritProject(ProjectStatus::FormallyVerified);
    $department = verificationDepartment();

    $assignment = app(AssignVerificationDepartmentAction::class)->execute(
        $project,
        $department,
        VerificationAssignmentType::MeritInitial,
        now()->addWeek(),
        'Sprawdzenie wstępne',
    );

    expect($assignment->type)->toBe(VerificationAssignmentType::MeritInitial)
        ->and($assignment->department_id)->toBe($department->id)
        ->and($assignment->project_id)->toBe($project->id);
});

it('submits positive initial merit verification and moves project forward', function (): void {
    $actor = User::factory()->create();
    $project = meritProject(ProjectStatus::FormallyVerified);
    $department = verificationDepartment();

    app(AssignVerificationDepartmentAction::class)->execute($project, $department, VerificationAssignmentType::MeritInitial);

    $verification = app(SubmitInitialMeritVerificationAction::class)->execute(
        $project,
        $department,
        $actor,
        true,
        ['mayorOfficeResult' => 1],
    );

    expect($verification->status)->toBe(VerificationCardStatus::Sent)
        ->and($verification->result)->toBeTrue()
        ->and($project->refresh()->status)->toBe(ProjectStatus::SentForMeritVerification)
        ->and($project->verificationAssignments()->first()->sent_at)->not->toBeNull()
        ->and(VerificationVersion::query()
            ->where('verification_legacy_id', $verification->id)
            ->where('type', VerificationAssignmentType::MeritInitial->value)
            ->count())->toBe(1);
});

it('waits for all initial merit departments before moving project forward', function (): void {
    $actor = User::factory()->create();
    $project = meritProject(ProjectStatus::DuringInitialVerification);
    $firstDepartment = verificationDepartment('Pierwsza jednostka');
    $secondDepartment = verificationDepartment('Druga jednostka');

    app(AssignVerificationDepartmentAction::class)->execute($project, $firstDepartment, VerificationAssignmentType::MeritInitial);
    app(AssignVerificationDepartmentAction::class)->execute($project, $secondDepartment, VerificationAssignmentType::MeritInitial);

    app(SubmitInitialMeritVerificationAction::class)->execute($project, $firstDepartment, $actor, true);

    expect($project->refresh()->status)->toBe(ProjectStatus::DuringInitialVerification);

    app(SubmitInitialMeritVerificationAction::class)->execute($project, $secondDepartment, $actor, true);

    expect($project->refresh()->status)->toBe(ProjectStatus::SentForMeritVerification);
});

it('returns sent initial merit verification card to working copy like legacy', function (): void {
    $actor = User::factory()->create();
    $project = meritProject(ProjectStatus::FormallyVerified);
    $department = verificationDepartment();

    app(AssignVerificationDepartmentAction::class)->execute($project, $department, VerificationAssignmentType::MeritInitial);
    $verification = app(SubmitInitialMeritVerificationAction::class)->execute($project, $department, $actor, true);

    $returned = app(ReturnVerificationCardAction::class)->execute($verification, $actor);
    $assignment = $project->verificationAssignments()
        ->where('department_id', $department->id)
        ->where('type', VerificationAssignmentType::MeritInitial->value)
        ->firstOrFail();

    expect($returned->status)->toBe(VerificationCardStatus::WorkingCopy)
        ->and($returned->sent_at)->toBeNull()
        ->and($assignment->sent_at)->toBeNull()
        ->and($assignment->is_returned)->toBeTrue()
        ->and(VerificationVersion::query()
            ->where('verification_legacy_id', $verification->id)
            ->where('type', VerificationAssignmentType::MeritInitial->value)
            ->count())->toBe(2);
});

it('requires assignment before sending initial merit verification', function (): void {
    $actor = User::factory()->create();
    $project = meritProject(ProjectStatus::FormallyVerified);
    $department = verificationDepartment();

    app(SubmitInitialMeritVerificationAction::class)->execute($project, $department, $actor, true);
})->throws(DomainException::class, 'Brak przydziału departamentu do wstępnej weryfikacji merytorycznej.');

it('requires reason for negative initial merit verification', function (): void {
    $actor = User::factory()->create();
    $project = meritProject(ProjectStatus::FormallyVerified);
    $department = verificationDepartment();

    app(AssignVerificationDepartmentAction::class)->execute($project, $department, VerificationAssignmentType::MeritInitial);

    app(SubmitInitialMeritVerificationAction::class)->execute($project, $department, $actor, false);
})->throws(DomainException::class, 'Negatywna wstępna weryfikacja merytoryczna wymaga uzasadnienia.');

it('submits negative final merit verification and rejects project', function (): void {
    $actor = User::factory()->create();
    $project = meritProject(ProjectStatus::SentForMeritVerification);
    $department = verificationDepartment();

    app(AssignVerificationDepartmentAction::class)->execute($project, $department, VerificationAssignmentType::MeritFinish);

    $verification = app(SubmitFinalMeritVerificationAction::class)->execute(
        $project,
        $department,
        $actor,
        false,
        resultComments: 'Brak możliwości realizacji.',
    );

    expect($verification->status)->toBe(VerificationCardStatus::Sent)
        ->and($verification->result)->toBeFalse()
        ->and($project->refresh()->status)->toBe(ProjectStatus::MeritVerificationRejected);
});

it('stores final merit corrected and future costs like legacy json fields', function (): void {
    $actor = User::factory()->create();
    $project = meritProject(ProjectStatus::SentForMeritVerification);
    $department = verificationDepartment();

    app(AssignVerificationDepartmentAction::class)->execute($project, $department, VerificationAssignmentType::MeritFinish);

    $verification = app(SubmitFinalMeritVerificationAction::class)->execute(
        $project,
        $department,
        $actor,
        true,
        correctedCosts: [
            ['description' => 'Prace', 'sum' => '1500.50'],
        ],
        futureCosts: [
            ['description' => 'Utrzymanie', 'sum' => 250],
        ],
    );

    expect($verification->answers['correctedCost'])->toBe([
        ['description' => 'Prace', 'sum' => 1500.5],
    ])
        ->and($verification->answers['futureCost'])->toBe([
            ['description' => 'Utrzymanie', 'sum' => 250],
        ])
        ->and($project->refresh()->status)->toBe(ProjectStatus::MeritVerificationAccepted)
        ->and(VerificationVersion::query()
            ->where('verification_legacy_id', $verification->id)
            ->where('type', VerificationAssignmentType::MeritFinish->value)
            ->count())->toBe(1);
});

it('rejects returning a verification card when assignment is missing', function (): void {
    $actor = User::factory()->create();
    $project = meritProject(ProjectStatus::SentForMeritVerification);
    $department = verificationDepartment();

    $verification = app(SubmitFinalMeritVerificationAction::class)->execute(
        $project,
        $department,
        $actor,
        true,
        sent: false,
    );

    app(ReturnVerificationCardAction::class)->execute($verification, $actor);
})->throws(DomainException::class, 'Nie znaleziono przydziału dla cofanej karty weryfikacji.');

it('ignores future costs when final card says there are no additional costs', function (): void {
    $actor = User::factory()->create();
    $project = meritProject(ProjectStatus::SentForMeritVerification);
    $department = verificationDepartment();

    app(AssignVerificationDepartmentAction::class)->execute($project, $department, VerificationAssignmentType::MeritFinish);

    $verification = app(SubmitFinalMeritVerificationAction::class)->execute(
        $project,
        $department,
        $actor,
        true,
        ['hasAdditionalCosts' => 0],
        futureCosts: [
            ['description' => '', 'sum' => ''],
        ],
    );

    expect($verification->answers['hasAdditionalCosts'])->toBe(0)
        ->and($verification->answers['futureCost'])->toBe([]);
});

it('waits for all final merit departments and rejects when any sent card is negative', function (): void {
    $actor = User::factory()->create();
    $project = meritProject(ProjectStatus::DuringMeritVerification);
    $firstDepartment = verificationDepartment('Pierwsza jednostka końcowa');
    $secondDepartment = verificationDepartment('Druga jednostka końcowa');

    app(AssignVerificationDepartmentAction::class)->execute($project, $firstDepartment, VerificationAssignmentType::MeritFinish);
    app(AssignVerificationDepartmentAction::class)->execute($project, $secondDepartment, VerificationAssignmentType::MeritFinish);

    app(SubmitFinalMeritVerificationAction::class)->execute($project, $firstDepartment, $actor, true);

    expect($project->refresh()->status)->toBe(ProjectStatus::DuringMeritVerification);

    app(SubmitFinalMeritVerificationAction::class)->execute(
        $project,
        $secondDepartment,
        $actor,
        false,
        resultComments: 'Negatywna opinia jednostki.',
    );

    expect($project->refresh()->status)->toBe(ProjectStatus::MeritVerificationRejected);
});

it('requires complete costs when final merit card is sent', function (): void {
    $actor = User::factory()->create();
    $project = meritProject(ProjectStatus::SentForMeritVerification);
    $department = verificationDepartment();

    app(AssignVerificationDepartmentAction::class)->execute($project, $department, VerificationAssignmentType::MeritFinish);

    app(SubmitFinalMeritVerificationAction::class)->execute(
        $project,
        $department,
        $actor,
        true,
        correctedCosts: [
            ['description' => '', 'sum' => 100],
        ],
    );
})->throws(DomainException::class, 'Koszt składowej dla kosztów szacunkowych nie może być pusty');

it('submits consultation without changing project status', function (): void {
    $actor = User::factory()->create();
    $project = meritProject(ProjectStatus::DuringMeritVerification);
    $department = verificationDepartment('Jednostka konsultująca');

    app(AssignVerificationDepartmentAction::class)->execute($project, $department, VerificationAssignmentType::Consultation);

    $verification = app(SubmitConsultationVerificationAction::class)->execute(
        $project,
        $department,
        $actor,
        true,
        ['consultationInformation' => 'Brak uwag'],
    );

    expect($verification->status)->toBe(VerificationCardStatus::Sent)
        ->and($verification->result)->toBeTrue()
        ->and($project->refresh()->status)->toBe(ProjectStatus::DuringMeritVerification)
        ->and(VerificationVersion::query()
            ->where('verification_legacy_id', $verification->id)
            ->where('type', VerificationAssignmentType::Consultation->value)
            ->count())->toBe(1);
});
