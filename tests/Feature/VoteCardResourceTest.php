<?php

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemRole;
use App\Domain\Voting\Enums\CitizenConfirmation;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Models\Voter;
use App\Filament\Resources\VoteCards\Pages\EditVoteCard;
use App\Filament\Resources\VoteCards\VoteCardResource;
use App\Models\User;

it('registers vote card filament resource pages and blocks creation', function (): void {
    expect(VoteCardResource::canCreate())->toBeFalse()
        ->and(array_keys(VoteCardResource::getPages()))->toBe(['index', 'edit']);
});

it('allows vote card resource access through vote card policy', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();
    $operator = User::factory()->create();
    $operator->assignRole(SystemRole::CheckVoter->value);
    $applicant = User::factory()->create();
    $applicant->assignRole(SystemRole::Applicant->value);

    $this->actingAs($operator);
    expect(VoteCardResource::canViewAny())->toBeTrue();

    $this->actingAs($applicant);
    expect(VoteCardResource::canViewAny())->toBeFalse();
});

it('updates vote card status in filament through domain action checkout', function (): void {
    $operator = User::factory()->create();
    $voter = Voter::query()->create([
        'pesel' => '44051401458',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
    ]);
    $voteCard = VoteCard::query()->create([
        'budget_edition_id' => budgetEdition()->id,
        'voter_id' => $voter->id,
        'status' => VoteCardStatus::Verifying,
    ]);

    $this->actingAs($operator);

    $page = new EditVoteCard;
    $method = new ReflectionMethod($page, 'handleRecordUpdate');
    $method->setAccessible(true);
    $method->invoke($page, $voteCard, [
        'status' => VoteCardStatus::Accepted->value,
        'notes' => 'Po ręcznej weryfikacji.',
    ]);

    expect($voteCard->refresh()->status)->toBe(VoteCardStatus::Accepted)
        ->and($voteCard->checkout_user_id)->toBe($operator->id)
        ->and($voteCard->checkout_date_time)->not->toBeNull()
        ->and($voteCard->notes)->toBe('Po ręcznej weryfikacji.');
});

it('registers paper vote card from filament form through domain action', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));
    $operator = User::factory()->create();
    $operator->assignRole(SystemRole::CheckVoter->value);

    $this->actingAs($operator);

    $voteCard = VoteCardResource::registerPaperVoteCardFromAdminForm([
        'budget_edition_id' => $edition->id,
        'pesel' => '44051401458',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
        'mother_last_name' => 'Nowak',
        'local_project_id' => $project->id,
        'citywide_project_id' => null,
        'citizen_confirm' => CitizenConfirmation::Living->value,
        'confirm_missing_category' => true,
    ]);

    expect($voteCard->digital)->toBeFalse()
        ->and($voteCard->card_no)->toBe(1)
        ->and($voteCard->created_by_id)->toBe($operator->id)
        ->and($voteCard->votes()->pluck('project_id')->all())->toBe([$project->id])
        ->and($edition->refresh()->current_paper_card_no)->toBe(1);
});

it('replaces existing vote card votes from filament form through domain action', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $oldProject = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));
    $newProject = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'title' => 'Nowy plac zabaw',
        'status' => ProjectStatus::Picked,
    ]));
    $voter = Voter::query()->create([
        'pesel' => '44051401458',
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
        'project_id' => $oldProject->id,
        'points' => 1,
    ]);
    $operator = User::factory()->create();
    $operator->assignRole(SystemRole::CheckVoter->value);

    $this->actingAs($operator);

    $updated = VoteCardResource::replaceVoteCardVotesFromAdminForm($voteCard, [
        'local_project_id' => $newProject->id,
        'citywide_project_id' => null,
        'confirm_missing_category' => true,
    ]);

    expect($updated->votes()->pluck('project_id')->all())->toBe([$newProject->id])
        ->and($updated->checkout_user_id)->toBe($operator->id)
        ->and($updated->checkout_date_time)->not->toBeNull();
});
