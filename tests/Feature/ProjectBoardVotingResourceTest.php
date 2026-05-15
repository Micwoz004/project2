<?php

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemRole;
use App\Domain\Verification\Enums\BoardType;
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
