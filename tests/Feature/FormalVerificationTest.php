<?php

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Verification\Actions\BeginFormalVerificationAction;
use App\Domain\Verification\Actions\CompleteFormalVerificationAction;
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
