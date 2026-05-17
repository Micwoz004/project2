<?php

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemRole;
use App\Domain\Verification\Actions\CastProjectBoardVoteAction;
use App\Domain\Verification\Actions\CloseBoardVotingAction;
use App\Domain\Verification\Actions\DecideProjectAppealAction;
use App\Domain\Verification\Actions\RecordBoardVoteRejectionAction;
use App\Domain\Verification\Actions\RespondProjectAppealAction;
use App\Domain\Verification\Actions\RestartBoardVotingAction;
use App\Domain\Verification\Actions\StartBoardVotingAction;
use App\Domain\Verification\Actions\SubmitProjectAppealAction;
use App\Domain\Verification\Enums\AtVoteChoice;
use App\Domain\Verification\Enums\BoardDecision;
use App\Domain\Verification\Enums\BoardType;
use App\Domain\Verification\Enums\OtVoteChoice;
use App\Domain\Verification\Enums\ProjectAppealFirstDecision;
use App\Domain\Verification\Enums\ZkVoteChoice;
use App\Domain\Verification\Services\BoardDecisionResolver;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

function boardProject(ProjectStatus $status = ProjectStatus::MeritVerificationAccepted): Project
{
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());

    return project($edition->id, $area->id, [
        'status' => $status,
    ]);
}

it('starts OT board voting and marks previously rejected project', function (): void {
    $project = boardProject(ProjectStatus::MeritVerificationRejected);

    $updated = app(StartBoardVotingAction::class)->execute($project, BoardType::Ot);

    expect($updated->status)->toBe(ProjectStatus::DuringTeamVerification)
        ->and($updated->was_rejected)->toBeTrue();
});

it('allows one board vote per user project and type', function (): void {
    $project = boardProject(ProjectStatus::DuringTeamVerification);
    $user = User::factory()->create();

    app(CastProjectBoardVoteAction::class)->execute($project, $user, BoardType::Ot, OtVoteChoice::Accepted->value);

    app(CastProjectBoardVoteAction::class)->execute($project, $user, BoardType::Ot, OtVoteChoice::Withhold->value);
})->throws(DomainException::class, 'Użytkownik oddał już głos dla tego projektu i typu głosowania.');

it('rejects choices that do not belong to board type', function (): void {
    $project = boardProject(ProjectStatus::DuringTeamVerification);
    $user = User::factory()->create();

    app(CastProjectBoardVoteAction::class)->execute($project, $user, BoardType::Zk, OtVoteChoice::Accepted->value);
})->throws(DomainException::class, 'Nieprawidłowy wybór dla typu głosowania.');

it('resolves ZK positive vote into picked project and tie into unresolved decision', function (): void {
    $accepted = boardProject(ProjectStatus::DuringTeamVerification);
    $tie = boardProject(ProjectStatus::DuringTeamVerification);

    app(CastProjectBoardVoteAction::class)->execute($accepted, User::factory()->create(), BoardType::Zk, ZkVoteChoice::Up->value);
    app(CastProjectBoardVoteAction::class)->execute($accepted, User::factory()->create(), BoardType::Zk, ZkVoteChoice::Up->value);
    app(CastProjectBoardVoteAction::class)->execute($accepted, User::factory()->create(), BoardType::Zk, ZkVoteChoice::Down->value);

    $decision = app(BoardDecisionResolver::class)->apply($accepted, BoardType::Zk);

    expect($decision)->toBe(BoardDecision::Accepted)
        ->and($accepted->refresh()->status)->toBe(ProjectStatus::Picked);

    app(CastProjectBoardVoteAction::class)->execute($tie, User::factory()->create(), BoardType::Zk, ZkVoteChoice::Up->value);
    app(CastProjectBoardVoteAction::class)->execute($tie, User::factory()->create(), BoardType::Zk, ZkVoteChoice::Down->value);

    app(BoardDecisionResolver::class)->apply($tie, BoardType::Zk);
})->throws(DomainException::class, 'Głosowanie nie daje jednoznacznego rozstrzygnięcia.');

it('uses president ZK vote to resolve legacy four to four boundary', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();
    $project = boardProject(ProjectStatus::DuringTeamVerification);
    $president = User::factory()->create();
    $president->assignRole(SystemRole::PresidentZk->value);

    app(CastProjectBoardVoteAction::class)->execute($project, $president, BoardType::Zk, ZkVoteChoice::Up->value);

    foreach (range(1, 3) as $index) {
        app(CastProjectBoardVoteAction::class)->execute($project, User::factory()->create(), BoardType::Zk, ZkVoteChoice::Up->value);
        app(CastProjectBoardVoteAction::class)->execute($project, User::factory()->create(), BoardType::Zk, ZkVoteChoice::Down->value);
    }

    app(CastProjectBoardVoteAction::class)->execute($project, User::factory()->create(), BoardType::Zk, ZkVoteChoice::Down->value);

    $decision = app(BoardDecisionResolver::class)->apply($project, BoardType::Zk);

    expect($decision)->toBe(BoardDecision::Accepted)
        ->and($project->refresh()->status)->toBe(ProjectStatus::Picked);
});

it('resolves OT rejected and reverify outcomes like legacy process action', function (): void {
    $rejected = boardProject(ProjectStatus::DuringTeamVerification);
    $reverify = boardProject(ProjectStatus::DuringTeamVerification);

    app(CastProjectBoardVoteAction::class)->execute($rejected, User::factory()->create(), BoardType::Ot, OtVoteChoice::RejectedWithRecall->value);
    app(CastProjectBoardVoteAction::class)->execute($rejected, User::factory()->create(), BoardType::Ot, OtVoteChoice::RejectedWithRecall->value);
    app(CastProjectBoardVoteAction::class)->execute($rejected, User::factory()->create(), BoardType::Ot, OtVoteChoice::VerifyAgain->value);

    $rejectedDecision = app(BoardDecisionResolver::class)->apply($rejected, BoardType::Ot);

    app(CastProjectBoardVoteAction::class)->execute($reverify, User::factory()->create(), BoardType::Ot, OtVoteChoice::VerifyAgain->value);
    app(CastProjectBoardVoteAction::class)->execute($reverify, User::factory()->create(), BoardType::Ot, OtVoteChoice::RejectedWithRecall->value);
    app(CastProjectBoardVoteAction::class)->execute($reverify, User::factory()->create(), BoardType::Ot, OtVoteChoice::VerifyAgain->value);

    $reverifyDecision = app(BoardDecisionResolver::class)->apply($reverify, BoardType::Ot);

    expect($rejectedDecision)->toBe(BoardDecision::RejectedWithRecall)
        ->and($rejected->refresh()->status)->toBe(ProjectStatus::TeamRejected)
        ->and($reverifyDecision)->toBe(BoardDecision::VerifyAgain)
        ->and($reverify->refresh()->status)->toBe(ProjectStatus::TeamForReverification);
});

it('resolves AT appeal accepted or finally rejected', function (): void {
    $accepted = boardProject(ProjectStatus::DuringTeamRecallVerification);
    $rejected = boardProject(ProjectStatus::DuringTeamRecallVerification);

    app(CastProjectBoardVoteAction::class)->execute($accepted, User::factory()->create(), BoardType::At, AtVoteChoice::AcceptedToVote->value);
    app(CastProjectBoardVoteAction::class)->execute($accepted, User::factory()->create(), BoardType::At, AtVoteChoice::Rejected->value);
    app(CastProjectBoardVoteAction::class)->execute($accepted, User::factory()->create(), BoardType::At, AtVoteChoice::AcceptedToVote->value);

    app(CastProjectBoardVoteAction::class)->execute($rejected, User::factory()->create(), BoardType::At, AtVoteChoice::Rejected->value);
    app(CastProjectBoardVoteAction::class)->execute($rejected, User::factory()->create(), BoardType::At, AtVoteChoice::Rejected->value);
    app(CastProjectBoardVoteAction::class)->execute($rejected, User::factory()->create(), BoardType::At, AtVoteChoice::AcceptedToVote->value);

    $acceptedDecision = app(BoardDecisionResolver::class)->apply($accepted, BoardType::At);
    $rejectedDecision = app(BoardDecisionResolver::class)->apply($rejected, BoardType::At);

    expect($acceptedDecision)->toBe(BoardDecision::Accepted)
        ->and($accepted->refresh()->status)->toBe(ProjectStatus::Picked)
        ->and($rejectedDecision)->toBe(BoardDecision::Rejected)
        ->and($rejected->refresh()->status)->toBe(ProjectStatus::TeamRejectedFinally);
});

it('submits a project appeal for rejected project author and blocks duplicates', function (): void {
    $author = User::factory()->create();
    $project = boardProject(ProjectStatus::TeamRejectedWithRecall);
    $project->forceFill(['creator_id' => $author->id])->save();

    $appeal = app(SubmitProjectAppealAction::class)->execute($project, $author, 'Proszę o ponowną ocenę.');

    expect($appeal->project_id)->toBe($project->id)
        ->and($appeal->appeal_message)->toBe('Proszę o ponowną ocenę.')
        ->and($project->refresh()->status)->toBe(ProjectStatus::TeamRejectedWithRecall);

    app(SubmitProjectAppealAction::class)->execute($project, $author, 'Drugie odwołanie.');
})->throws(DomainException::class, 'Dla projektu istnieje już odwołanie.');

it('decides appeal preliminary review with legacy firstDecision values', function (): void {
    $actor = User::factory()->create();
    $acceptedProject = boardProject(ProjectStatus::TeamRejectedWithRecall);
    $rejectedProject = boardProject(ProjectStatus::TeamRejectedWithRecall);
    $acceptedProject->forceFill(['creator_id' => $actor->id])->save();
    $rejectedProject->forceFill(['creator_id' => $actor->id])->save();

    $acceptedAppeal = app(SubmitProjectAppealAction::class)->execute($acceptedProject, $actor, 'Odwołanie przyjęte.');
    $rejectedAppeal = app(SubmitProjectAppealAction::class)->execute($rejectedProject, $actor, 'Odwołanie odrzucone.');

    $acceptedDecision = app(DecideProjectAppealAction::class)->execute(
        $acceptedAppeal,
        $actor,
        ProjectAppealFirstDecision::Accepted,
    );
    $rejectedDecision = app(DecideProjectAppealAction::class)->execute(
        $rejectedAppeal,
        $actor,
        ProjectAppealFirstDecision::Rejected,
    );

    expect(ProjectAppealFirstDecision::Rejected->value)->toBe(1)
        ->and(ProjectAppealFirstDecision::Accepted->value)->toBe(2)
        ->and($acceptedDecision->first_decision)->toBe(ProjectAppealFirstDecision::Accepted->value)
        ->and($acceptedProject->refresh()->status)->toBe(ProjectStatus::FormallyVerified)
        ->and($rejectedDecision->first_decision)->toBe(ProjectAppealFirstDecision::Rejected->value)
        ->and($rejectedProject->refresh()->status)->toBe(ProjectStatus::TeamRejectedWithRecall);
});

it('records appeal response with response date like legacy model', function (): void {
    $actor = User::factory()->create();
    $project = boardProject(ProjectStatus::TeamRejectedWithRecall);
    $project->forceFill(['creator_id' => $actor->id])->save();
    $appeal = app(SubmitProjectAppealAction::class)->execute($project, $actor, 'Treść odwołania.');

    $responded = app(RespondProjectAppealAction::class)->execute($appeal, $actor, 'Odpowiedź komisji.');

    expect($responded->response_to_appeal)->toBe('Odpowiedź komisji.')
        ->and($responded->response_created_at)->not->toBeNull();
});

it('records rejection comments only for OT and AT', function (): void {
    $project = boardProject(ProjectStatus::DuringTeamVerification);
    $user = User::factory()->create();

    $rejection = app(RecordBoardVoteRejectionAction::class)->execute($project, $user, BoardType::Ot, 'Uzasadnienie rady.');

    expect($rejection->comment)->toBe('Uzasadnienie rady.')
        ->and($rejection->board_type)->toBe(BoardType::Ot);

    app(RecordBoardVoteRejectionAction::class)->execute($project, $user, BoardType::Zk, 'Nieobsługiwane.');
})->throws(DomainException::class, 'Uzasadnienia odrzucenia dotyczą tylko głosowań AT/OT.');

it('closes and restarts OT voting like legacy force actions', function (): void {
    $project = boardProject(ProjectStatus::DuringTeamVerification);

    app(CastProjectBoardVoteAction::class)->execute($project, User::factory()->create(), BoardType::Ot, OtVoteChoice::Accepted->value);

    $closed = app(CloseBoardVotingAction::class)->execute($project, BoardType::Ot);

    expect($closed->status)->toBe(ProjectStatus::TeamClosedVerification)
        ->and($project->boardVotes()->where('board_type', BoardType::Ot->value)->count())->toBe(1);

    $restarted = app(RestartBoardVotingAction::class)->execute($project, BoardType::Ot);

    expect($restarted->status)->toBe(ProjectStatus::DuringTeamVerification)
        ->and($project->boardVotes()->where('board_type', BoardType::Ot->value)->count())->toBe(0);
});

it('closes and restarts AT voting like legacy force actions', function (): void {
    $project = boardProject(ProjectStatus::DuringTeamRecallVerification);

    app(CastProjectBoardVoteAction::class)->execute($project, User::factory()->create(), BoardType::At, AtVoteChoice::Rejected->value);

    $closed = app(CloseBoardVotingAction::class)->execute($project, BoardType::At);

    expect($closed->status)->toBe(ProjectStatus::TeamRecallClosedVerification)
        ->and($project->boardVotes()->where('board_type', BoardType::At->value)->count())->toBe(1);

    $restarted = app(RestartBoardVotingAction::class)->execute($project, BoardType::At);

    expect($restarted->status)->toBe(ProjectStatus::DuringTeamRecallVerification)
        ->and($project->boardVotes()->where('board_type', BoardType::At->value)->count())->toBe(0);
});

it('authorizes board voting by legacy council roles', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $zkVerifier = User::factory()->create();
    $zkVerifier->assignRole(SystemRole::VerifierZk->value);
    $zodVerifier = User::factory()->create();
    $zodVerifier->assignRole(SystemRole::VerifierZod->value);
    $observer = User::factory()->create();
    $observer->assignRole(SystemRole::ObserverZk->value);

    expect(Gate::forUser($zkVerifier)->allows('cast-board-vote', BoardType::Zk))->toBeTrue()
        ->and(Gate::forUser($zkVerifier)->allows('cast-board-vote', BoardType::Ot))->toBeFalse()
        ->and(Gate::forUser($zodVerifier)->allows('cast-board-vote', BoardType::Ot))->toBeTrue()
        ->and(Gate::forUser($zodVerifier)->allows('cast-board-vote', BoardType::At))->toBeTrue()
        ->and(Gate::forUser($observer)->allows('cast-board-vote', BoardType::Zk))->toBeFalse();
});

it('authorizes board voting management only for project managers', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $verifier = User::factory()->create();
    $verifier->assignRole(SystemRole::VerifierZk->value);

    expect(Gate::forUser($coordinator)->allows('manage-board-voting'))->toBeTrue()
        ->and(Gate::forUser($verifier)->allows('manage-board-voting'))->toBeFalse();
});
