<?php

use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemRole;
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
