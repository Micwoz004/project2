<?php

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Results\Services\ResultsPublicationService;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Models\Voter;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

it('publishes public results only during result announcement state', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-05-15 12:00:00', 'Europe/Warsaw'));

    $notPublished = BudgetEdition::query()->create(publicResultsEditionAttributes([
        'post_voting_verification_end' => '2026-05-20 12:00:00',
    ]));
    $published = BudgetEdition::query()->create(publicResultsEditionAttributes());

    $service = app(ResultsPublicationService::class);

    expect($service->canPublishPublicResults($notPublished))->toBeFalse()
        ->and($service->canPublishPublicResults($published))->toBeTrue();
});

it('hides public results before publication window', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-05-15 12:00:00', 'Europe/Warsaw'));

    $edition = BudgetEdition::query()->create(publicResultsEditionAttributes([
        'post_voting_verification_end' => '2026-05-20 12:00:00',
    ]));
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'title' => 'Ukryty wynik',
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
        'project_id' => $project->id,
        'points' => 1,
    ]);

    $this->get(route('public.results.index'))
        ->assertOk()
        ->assertSee('Wyniki nie zostały jeszcze opublikowane.')
        ->assertDontSee('Ukryty wynik');
});

it('shows public results during publication window', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-05-15 12:00:00', 'Europe/Warsaw'));

    $edition = BudgetEdition::query()->create(publicResultsEditionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'title' => 'Opublikowany wynik',
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
        'project_id' => $project->id,
        'points' => 1,
    ]);

    $this->get(route('public.results.index'))
        ->assertOk()
        ->assertSee('Opublikowany wynik')
        ->assertSee('1');
});

function publicResultsEditionAttributes(array $overrides = []): array
{
    return [
        'propose_start' => '2026-01-01 00:00:00',
        'propose_end' => '2026-02-01 00:00:00',
        'pre_voting_verification_end' => '2026-03-01 00:00:00',
        'voting_start' => '2026-04-01 00:00:00',
        'voting_end' => '2026-05-10 12:00:00',
        'post_voting_verification_end' => '2026-05-14 12:00:00',
        'result_announcement_end' => '2026-05-30 12:00:00',
        ...$overrides,
    ];
}
