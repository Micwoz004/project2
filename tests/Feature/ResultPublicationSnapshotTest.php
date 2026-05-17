<?php

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Results\Actions\PublishResultSnapshotAction;
use App\Domain\Results\Models\ResultPublication;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Models\Voter;
use App\Models\User;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

it('stores auditable result snapshot during publication window', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-05-15 12:00:00', 'Europe/Warsaw'));

    [$edition, $project] = createResultSnapshotFixture();
    $operator = resultSnapshotOperator();

    $publication = app(PublishResultSnapshotAction::class)->execute($edition, $operator);

    expect($publication)->toBeInstanceOf(ResultPublication::class)
        ->and($publication->budget_edition_id)->toBe($edition->id)
        ->and($publication->published_by_id)->toBe($operator->id)
        ->and($publication->version)->toBe(1)
        ->and($publication->total_points)->toBe(1)
        ->and($publication->projects_count)->toBe(1)
        ->and($publication->project_totals[0]['project_id'])->toBe($project->id)
        ->and($publication->project_totals[0]['points'])->toBe(1)
        ->and($publication->area_totals[0]['points'])->toBe(1)
        ->and($publication->category_totals[0]['points'])->toBe(1)
        ->and($publication->status_counts['ważna'])->toBe(1);
});

it('increments result snapshot version for every publication', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-05-15 12:00:00', 'Europe/Warsaw'));

    [$edition] = createResultSnapshotFixture();
    $operator = resultSnapshotOperator();
    $action = app(PublishResultSnapshotAction::class);

    $first = $action->execute($edition, $operator);
    $second = $action->execute($edition, $operator);

    expect($first->version)->toBe(1)
        ->and($second->version)->toBe(2)
        ->and(ResultPublication::query()->where('budget_edition_id', $edition->id)->count())->toBe(2);
});

it('rejects result snapshot without report export permission', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-05-15 12:00:00', 'Europe/Warsaw'));

    [$edition] = createResultSnapshotFixture();
    $operator = User::factory()->create(['status' => true]);

    app(PublishResultSnapshotAction::class)->execute($edition, $operator);
})->throws(DomainException::class, 'Brak uprawnień do utrwalenia publikacji wyników.');

it('rejects result snapshot before result publication window', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-05-15 12:00:00', 'Europe/Warsaw'));

    [$edition] = createResultSnapshotFixture([
        'post_voting_verification_end' => '2026-05-20 12:00:00',
    ]);
    $operator = resultSnapshotOperator();

    app(PublishResultSnapshotAction::class)->execute($edition, $operator);
})->throws(DomainException::class, 'Wyniki można utrwalić dopiero w etapie publikacji.');

function createResultSnapshotFixture(array $editionOverrides = []): array
{
    $edition = BudgetEdition::query()->create(resultSnapshotEditionAttributes($editionOverrides));
    $area = ProjectArea::query()->create(areaAttributes());
    $category = Category::query()->create(['name' => 'Zieleń']);
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'number_drawn' => 7,
        'status' => ProjectStatus::Picked,
        'category_id' => $category->id,
    ]));
    $project->categories()->attach($category);

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

function resultSnapshotEditionAttributes(array $overrides = []): array
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

function resultSnapshotOperator(): User
{
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $operator = User::factory()->create(['status' => true]);
    $operator->givePermissionTo(SystemPermission::ReportsExport->value);

    return $operator;
}
