<?php

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Results\Actions\ResolveResultTieDecisionAction;
use App\Domain\Results\Models\ResultTieDecision;
use App\Domain\Results\Services\ResultTieBreakerService;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Models\Voter;
use App\Models\User;

it('stores manual result tie decision for an active tied group', function (): void {
    [$edition, $firstProject, $secondProject] = createResultTieDecisionFixture();
    $operator = resultTieDecisionOperator();

    $decision = app(ResolveResultTieDecisionAction::class)->execute(
        $edition,
        [$firstProject->id, $secondProject->id],
        $secondProject,
        $operator,
        'Decyzja komisji po analizie remisu.',
    );

    $group = app(ResultTieBreakerService::class)->tiedProjectGroups($edition)->first();

    expect($decision)->toBeInstanceOf(ResultTieDecision::class)
        ->and($decision->winner_project_id)->toBe($secondProject->id)
        ->and($decision->decided_by_id)->toBe($operator->id)
        ->and($decision->project_ids)->toBe([
            $firstProject->id,
            $secondProject->id,
        ])
        ->and($group['requires_manual_decision'])->toBeFalse()
        ->and($group['decision']['winner_project_id'])->toBe($secondProject->id);
});

it('rejects result tie decision without report permissions', function (): void {
    [$edition, $firstProject, $secondProject] = createResultTieDecisionFixture();
    $operator = User::factory()->create();

    app(ResolveResultTieDecisionAction::class)->execute(
        $edition,
        [$firstProject->id, $secondProject->id],
        $firstProject,
        $operator,
    );
})->throws(DomainException::class, 'Brak uprawnień do rozstrzygnięcia remisu wyników.');

it('rejects result tie decision for projects that are not an active tie', function (): void {
    [$edition, $firstProject, $secondProject] = createResultTieDecisionFixture();
    $operator = resultTieDecisionOperator();

    $voter = Voter::query()->create([
        'pesel' => '44051401459',
        'first_name' => 'Anna',
        'last_name' => 'Nowak',
    ]);
    $voteCard = VoteCard::query()->create([
        'budget_edition_id' => $edition->id,
        'voter_id' => $voter->id,
        'status' => VoteCardStatus::Accepted,
    ]);
    $voteCard->votes()->create([
        'voter_id' => $voter->id,
        'project_id' => $firstProject->id,
        'points' => 1,
    ]);

    app(ResolveResultTieDecisionAction::class)->execute(
        $edition,
        [$firstProject->id, $secondProject->id],
        $firstProject,
        $operator,
    );
})->throws(DomainException::class, 'Nie znaleziono aktywnego remisu dla wskazanych projektów.');

function createResultTieDecisionFixture(): array
{
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $firstProject = project($edition->id, $area->id, [
        'number_drawn' => 1,
        'status' => ProjectStatus::Picked,
    ]);
    $secondProject = project($edition->id, $area->id, [
        'number_drawn' => 2,
        'status' => ProjectStatus::Picked,
    ]);

    foreach ([$firstProject, $secondProject] as $index => $project) {
        $voter = Voter::query()->create([
            'pesel' => '4405140145'.$index,
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
        ]);
        $voteCard = VoteCard::query()->create([
            'budget_edition_id' => $edition->id,
            'voter_id' => $voter->id,
            'status' => VoteCardStatus::Accepted,
        ]);
        $voteCard->votes()->create([
            'voter_id' => $voter->id,
            'project_id' => $project->id,
            'points' => 1,
        ]);
    }

    return [$edition, $firstProject, $secondProject];
}

function resultTieDecisionOperator(): User
{
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $operator = User::factory()->create();
    $operator->givePermissionTo(SystemPermission::ReportsExport->value);

    return $operator;
}
