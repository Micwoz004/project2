<?php

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Voting\Enums\CitizenConfirmation;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Models\VotingToken;
use App\Livewire\PublicVotingFlow;
use Livewire\Livewire;

it('issues sms token through public voting endpoint', function (): void {
    $this->from(route('public.voting.welcome'))
        ->post(route('public.voting.token'), voterPayload())
        ->assertRedirect(route('public.voting.welcome'));

    expect(VotingToken::query()->count())->toBe(1)
        ->and(VotingToken::query()->firstOrFail()->token)->toMatch('/^[0-9]{6}$/');
});

it('casts public vote with activated sms token', function (): void {
    $edition = budgetEdition();
    $localArea = ProjectArea::query()->create(areaAttributes());
    $citywideArea = ProjectArea::query()->create(areaAttributes([
        'name' => 'Ogólnomiejskie',
        'symbol' => 'OGM',
        'is_local' => false,
    ]));
    $localProject = Project::query()->create(projectAttributes($edition->id, $localArea->id, [
        'status' => ProjectStatus::Picked,
        'number_drawn' => 1,
    ]));
    $citywideProject = Project::query()->create(projectAttributes($edition->id, $citywideArea->id, [
        'title' => 'Projekt ogólnomiejski',
        'status' => ProjectStatus::Picked,
        'number_drawn' => 2,
    ]));

    $this->from(route('public.voting.welcome'))
        ->post(route('public.voting.token'), voterPayload());
    $token = VotingToken::query()->firstOrFail();

    $this->post(route('public.voting.cast'), [
        ...voterPayload(),
        'budget_edition_id' => $edition->id,
        'sms_token' => $token->token,
        'local_project_id' => $localProject->id,
        'citywide_project_id' => $citywideProject->id,
        'citizen_confirm' => CitizenConfirmation::Living->value,
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('public.voting.welcome'));

    $voteCard = VoteCard::query()->firstOrFail();

    expect($voteCard->status)->toBe(VoteCardStatus::Accepted)
        ->and($voteCard->votes()->pluck('project_id')->sort()->values()->all())
        ->toBe(collect([$localProject->id, $citywideProject->id])->sort()->values()->toArray())
        ->and($token->refresh()->disabled)->toBeTrue();
});

it('issues sms token through livewire voting flow', function (): void {
    budgetEdition();

    Livewire::test(PublicVotingFlow::class)
        ->set('pesel', voterPayload()['pesel'])
        ->set('firstName', voterPayload()['first_name'])
        ->set('lastName', voterPayload()['last_name'])
        ->set('motherLastName', voterPayload()['mother_last_name'])
        ->set('phone', voterPayload()['phone'])
        ->call('issueToken')
        ->assertHasNoErrors()
        ->assertSet('statusMessage', 'Kod SMS został przygotowany.');

    expect(VotingToken::query()->count())->toBe(1);
});

it('casts vote through livewire voting flow', function (): void {
    $edition = budgetEdition();
    $localArea = ProjectArea::query()->create(areaAttributes());
    $citywideArea = ProjectArea::query()->create(areaAttributes([
        'name' => 'Ogólnomiejskie',
        'symbol' => 'OGM',
        'is_local' => false,
    ]));
    $localProject = Project::query()->create(projectAttributes($edition->id, $localArea->id, [
        'status' => ProjectStatus::Picked,
        'number_drawn' => 1,
    ]));
    $citywideProject = Project::query()->create(projectAttributes($edition->id, $citywideArea->id, [
        'title' => 'Projekt ogólnomiejski',
        'status' => ProjectStatus::Picked,
        'number_drawn' => 2,
    ]));

    $this->post(route('public.voting.token'), voterPayload());
    $token = VotingToken::query()->firstOrFail();

    Livewire::test(PublicVotingFlow::class)
        ->set('budgetEditionId', $edition->id)
        ->set('pesel', voterPayload()['pesel'])
        ->set('firstName', voterPayload()['first_name'])
        ->set('lastName', voterPayload()['last_name'])
        ->set('motherLastName', voterPayload()['mother_last_name'])
        ->set('phone', voterPayload()['phone'])
        ->set('smsToken', $token->token)
        ->set('localProjectId', $localProject->id)
        ->set('citywideProjectId', $citywideProject->id)
        ->set('citizenConfirm', CitizenConfirmation::Living->value)
        ->call('cast')
        ->assertHasNoErrors()
        ->assertSet('statusMessage', 'Głos został zapisany.');

    expect(VoteCard::query()->firstOrFail()->status)->toBe(VoteCardStatus::Accepted)
        ->and($token->refresh()->disabled)->toBeTrue();
});

function voterPayload(): array
{
    return [
        'pesel' => '44051401458',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
        'mother_last_name' => 'Nowak',
        'phone' => '500600700',
    ];
}
