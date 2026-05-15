<?php

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Reports\Exports\PublicResultsCsvExporter;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Models\Voter;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

it('exports public results csv without voter pii', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-05-15 12:00:00', 'Europe/Warsaw'));

    [$edition, $project] = createResultForPublicExport();

    $csv = app(PublicResultsCsvExporter::class)->export($edition);

    expect($csv)->toContain('project_id,project_number,title,area,points')
        ->and($csv)->toContain($project->id.',7,"Park kieszonkowy","Obszar lokalny",1')
        ->and($csv)->not->toContain('44051401458')
        ->and($csv)->not->toContain('Kowalski');
});

it('allows public csv export only during results publication window', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-05-15 12:00:00', 'Europe/Warsaw'));

    createResultForPublicExport();

    $this->get(route('public.results.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('blocks public csv export before results publication window', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-05-15 12:00:00', 'Europe/Warsaw'));

    BudgetEdition::query()->create(publicResultsExportEditionAttributes([
        'post_voting_verification_end' => '2026-05-20 12:00:00',
    ]));

    $this->get(route('public.results.export'))->assertNotFound();
});

function createResultForPublicExport(): array
{
    $edition = BudgetEdition::query()->create(publicResultsExportEditionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'number' => 7,
        'title' => 'Park kieszonkowy',
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

    return [$edition, $project];
}

function publicResultsExportEditionAttributes(array $overrides = []): array
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
