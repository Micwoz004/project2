<?php

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemRole;
use App\Domain\Verification\Actions\CastProjectBoardVoteAction;
use App\Domain\Verification\Enums\BoardType;
use App\Domain\Verification\Enums\OtVoteChoice;
use App\Domain\Verification\Enums\ProjectAppealFirstDecision;
use App\Domain\Verification\Models\ProjectAppeal;
use App\Filament\Resources\ProjectAppeals\ProjectAppealResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\User;

function boardVotingResourceProject(ProjectStatus $status): Project
{
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());

    return project($edition->id, $area->id, [
        'status' => $status,
    ]);
}

it('shows OT board close and restart actions only for project managers and matching statuses', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $open = boardVotingResourceProject(ProjectStatus::DuringTeamVerification);
    $closed = boardVotingResourceProject(ProjectStatus::TeamClosedVerification);
    $picked = boardVotingResourceProject(ProjectStatus::Picked);

    expect(ProjectResource::canCloseBoardVoting($open, BoardType::Ot))->toBeTrue()
        ->and(ProjectResource::canRestartBoardVoting($open, BoardType::Ot))->toBeTrue()
        ->and(ProjectResource::canCloseBoardVoting($closed, BoardType::Ot))->toBeFalse()
        ->and(ProjectResource::canRestartBoardVoting($closed, BoardType::Ot))->toBeTrue()
        ->and(ProjectResource::canCloseBoardVoting($picked, BoardType::Ot))->toBeFalse()
        ->and(ProjectResource::canRestartBoardVoting($picked, BoardType::Ot))->toBeFalse()
        ->and(ProjectResource::canCloseBoardVoting($open, BoardType::Zk))->toBeFalse()
        ->and(ProjectResource::canRestartBoardVoting($open, BoardType::Zk))->toBeFalse();
});

it('shows AT board close and restart actions only for appeal statuses', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $appealOpen = boardVotingResourceProject(ProjectStatus::DuringTeamRecallVerification);
    $appealClosed = boardVotingResourceProject(ProjectStatus::TeamRecallClosedVerification);
    $otOpen = boardVotingResourceProject(ProjectStatus::DuringTeamVerification);

    expect(ProjectResource::canCloseBoardVoting($appealOpen, BoardType::At))->toBeTrue()
        ->and(ProjectResource::canRestartBoardVoting($appealOpen, BoardType::At))->toBeTrue()
        ->and(ProjectResource::canCloseBoardVoting($appealClosed, BoardType::At))->toBeFalse()
        ->and(ProjectResource::canRestartBoardVoting($appealClosed, BoardType::At))->toBeTrue()
        ->and(ProjectResource::canCloseBoardVoting($otOpen, BoardType::At))->toBeFalse()
        ->and(ProjectResource::canRestartBoardVoting($otOpen, BoardType::At))->toBeFalse();
});

it('hides board management actions from users without project management permission', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $verifier = User::factory()->create();
    $verifier->assignRole(SystemRole::VerifierZk->value);
    $this->actingAs($verifier);

    $open = boardVotingResourceProject(ProjectStatus::DuringTeamVerification);
    $appealOpen = boardVotingResourceProject(ProjectStatus::DuringTeamRecallVerification);

    expect(ProjectResource::canCloseBoardVoting($open, BoardType::Ot))->toBeFalse()
        ->and(ProjectResource::canRestartBoardVoting($open, BoardType::Ot))->toBeFalse()
        ->and(ProjectResource::canCloseBoardVoting($appealOpen, BoardType::At))->toBeFalse()
        ->and(ProjectResource::canRestartBoardVoting($appealOpen, BoardType::At))->toBeFalse();
});

it('shows board vote actions for council roles by board type and project status', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $zkVerifier = User::factory()->create();
    $zkVerifier->assignRole(SystemRole::VerifierZk->value);
    $this->actingAs($zkVerifier);

    $zkProject = boardVotingResourceProject(ProjectStatus::DuringTeamVerification);

    expect(ProjectResource::canCastBoardVote($zkProject, BoardType::Zk))->toBeTrue()
        ->and(ProjectResource::canCastBoardVote($zkProject, BoardType::Ot))->toBeFalse();

    $zodVerifier = User::factory()->create();
    $zodVerifier->assignRole(SystemRole::VerifierZod->value);
    $this->actingAs($zodVerifier);

    $otProject = boardVotingResourceProject(ProjectStatus::DuringTeamVerification);
    $atProject = boardVotingResourceProject(ProjectStatus::DuringTeamRecallVerification);

    expect(ProjectResource::canCastBoardVote($otProject, BoardType::Ot))->toBeTrue()
        ->and(ProjectResource::canCastBoardVote($otProject, BoardType::At))->toBeFalse()
        ->and(ProjectResource::canCastBoardVote($atProject, BoardType::At))->toBeTrue()
        ->and(ProjectResource::canCastBoardVote($atProject, BoardType::Ot))->toBeFalse();
});

it('hides board vote action after current user has already voted', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $verifier = User::factory()->create();
    $verifier->assignRole(SystemRole::VerifierZod->value);
    $this->actingAs($verifier);

    $project = boardVotingResourceProject(ProjectStatus::DuringTeamVerification);

    expect(ProjectResource::canCastBoardVote($project, BoardType::Ot))->toBeTrue();

    app(CastProjectBoardVoteAction::class)->execute($project, $verifier, BoardType::Ot, OtVoteChoice::Accepted->value);

    expect(ProjectResource::canCastBoardVote($project, BoardType::Ot))->toBeFalse();
});

it('handles project appeals from filament actions through domain logic', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $project = boardVotingResourceProject(ProjectStatus::TeamRejectedWithRecall);

    expect(ProjectResource::canSubmitProjectAppeal($project))->toBeTrue()
        ->and(ProjectResource::canDecideProjectAppeal($project))->toBeFalse()
        ->and(ProjectResource::canRespondProjectAppeal($project))->toBeFalse();

    $appeal = ProjectResource::submitProjectAppealFromAdminForm($project, [
        'appeal_message' => 'Odwołanie administratora.',
    ]);

    expect($appeal->appeal_message)->toBe('Odwołanie administratora.')
        ->and(ProjectResource::canSubmitProjectAppeal($project->refresh()))->toBeFalse()
        ->and(ProjectResource::canDecideProjectAppeal($project))->toBeTrue()
        ->and(ProjectResource::canRespondProjectAppeal($project))->toBeTrue();

    $decided = ProjectResource::decideProjectAppealFromAdminForm($project, [
        'first_decision' => ProjectAppealFirstDecision::Accepted->value,
    ]);

    expect($decided->first_decision)->toBe(ProjectAppealFirstDecision::Accepted->value)
        ->and($project->refresh()->status)->toBe(ProjectStatus::FormallyVerified);

    $responded = ProjectResource::respondProjectAppealFromAdminForm($project, [
        'response_to_appeal' => 'Odpowiedź komisji.',
    ]);

    expect($responded->response_to_appeal)->toBe('Odpowiedź komisji.')
        ->and($responded->response_created_at)->not->toBeNull();
});

it('shows project appeals in read only filament resource for project managers', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create(['status' => true]);
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $applicant = User::factory()->create(['status' => true]);
    $applicant->assignRole(SystemRole::Applicant->value);
    $project = boardVotingResourceProject(ProjectStatus::TeamRejectedWithRecall);

    ProjectAppeal::query()->create([
        'project_id' => $project->id,
        'appeal_message' => 'Treść odwołania do komisji.',
        'first_decision' => ProjectAppealFirstDecision::Accepted->value,
        'response_to_appeal' => 'Odpowiedź komisji.',
        'response_created_at' => now(),
    ]);

    $this->actingAs($coordinator)
        ->get(ProjectAppealResource::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('Treść odwołania do komisji.')
        ->assertSee('przyjęte');

    $this->actingAs($applicant)
        ->get(ProjectAppealResource::getUrl(panel: 'admin'))
        ->assertForbidden();

    expect(array_keys(ProjectAppealResource::getPages()))->toBe(['index'])
        ->and(ProjectAppealResource::canCreate())->toBeFalse();
});
