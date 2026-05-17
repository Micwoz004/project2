<?php

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Models\Voter;
use App\Filament\Pages\ResultsDashboard;
use App\Models\User;

it('shows administrative results dashboard for users with results permission', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    [$edition, $projectA, $projectB] = createDashboardResultsFixture();
    $user = User::factory()->create(['status' => true]);
    $user->givePermissionTo(SystemPermission::AdminAccess->value);
    $user->givePermissionTo(SystemPermission::ResultsView->value);
    $user->givePermissionTo(SystemPermission::ReportsExport->value);

    $this->actingAs($user)
        ->get(ResultsDashboard::getUrl(['budget_edition_id' => $edition->id], panel: 'admin'))
        ->assertOk()
        ->assertSee('Wyniki i publikacja')
        ->assertSee($projectA->title)
        ->assertSee($projectB->title)
        ->assertSee('Remisy wymagające decyzji')
        ->assertSee('ważna')
        ->assertSee('nieważna')
        ->assertSee('Kategorie CSV');
});

it('blocks administrative results dashboard without results permission', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $user = User::factory()->create(['status' => true]);
    $user->givePermissionTo(SystemPermission::AdminAccess->value);

    $this->actingAs($user)
        ->get(ResultsDashboard::getUrl(panel: 'admin'))
        ->assertForbidden();
});

function createDashboardResultsFixture(): array
{
    $edition = BudgetEdition::query()->create([
        ...editionAttributes(),
        'voting_end' => now()->subDay(),
        'post_voting_verification_end' => now()->subHour(),
        'result_announcement_end' => now()->addWeek(),
    ]);
    $area = ProjectArea::query()->create(areaAttributes());
    $category = Category::query()->create(['name' => 'Zieleń']);
    $projectA = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'number_drawn' => 1,
        'title' => 'Park kieszonkowy A',
        'status' => ProjectStatus::Picked,
        'category_id' => $category->id,
    ]));
    $projectB = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'number_drawn' => 2,
        'title' => 'Park kieszonkowy B',
        'status' => ProjectStatus::Picked,
        'category_id' => $category->id,
    ]));
    $projectA->categories()->attach($category);

    $voter = Voter::query()->create([
        'pesel' => '44051401458',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
    ]);
    $acceptedCard = VoteCard::query()->create([
        'budget_edition_id' => $edition->id,
        'voter_id' => $voter->id,
        'status' => VoteCardStatus::Accepted,
    ]);
    $acceptedCard->votes()->create([
        'voter_id' => $voter->id,
        'project_id' => $projectA->id,
        'points' => 1,
    ]);
    $acceptedCard->votes()->create([
        'voter_id' => $voter->id,
        'project_id' => $projectB->id,
        'points' => 1,
    ]);

    VoteCard::query()->create([
        'budget_edition_id' => $edition->id,
        'voter_id' => $voter->id,
        'status' => VoteCardStatus::Rejected,
    ]);

    return [$edition, $projectA, $projectB];
}
