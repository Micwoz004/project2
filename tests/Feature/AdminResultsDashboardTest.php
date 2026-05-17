<?php

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Reports\Enums\AdminReportType;
use App\Domain\Reports\Enums\ReportExportFormat;
use App\Domain\Reports\Enums\ReportExportStatus;
use App\Domain\Reports\Jobs\GenerateAdminReportExportJob;
use App\Domain\Reports\Models\ReportExport;
use App\Domain\Results\Models\ResultPublication;
use App\Domain\Results\Models\ResultTieDecision;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Models\Voter;
use App\Filament\Pages\ResultsDashboard;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;

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
        ->assertSee('Remisy i decyzje manualne')
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

it('stores manual result tie decision from administrative dashboard', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    [$edition, $projectA] = createDashboardResultsFixture();
    $user = User::factory()->create(['status' => true]);
    $user->givePermissionTo(SystemPermission::AdminAccess->value);
    $user->givePermissionTo(SystemPermission::ResultsView->value);
    $user->givePermissionTo(SystemPermission::ReportsExport->value);

    $this->actingAs($user);

    $component = Livewire::test(ResultsDashboard::class)
        ->set('budgetEditionId', $edition->id)
        ->call('loadDashboard');

    $summary = $component->get('summary');
    $formKey = $summary['tie_groups'][0]['form_key'];

    $component
        ->set('tieDecisionWinners.'.$formKey, $projectA->id)
        ->set('tieDecisionNotes.'.$formKey, 'Decyzja komisji po analizie remisu.')
        ->call('resolveTieDecision', $formKey)
        ->assertHasNoErrors();

    $decision = ResultTieDecision::query()->firstOrFail();
    $updatedSummary = $component->get('summary');

    expect($decision->winner_project_id)->toBe($projectA->id)
        ->and($decision->decided_by_id)->toBe($user->id)
        ->and($updatedSummary['tie_groups'][0]['requires_manual_decision'])->toBeFalse()
        ->and($updatedSummary['tie_groups'][0]['decision']['winner_project_id'])->toBe($projectA->id);
});

it('stores result publication snapshot from administrative dashboard', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    [$edition] = createDashboardResultsFixture();
    $user = User::factory()->create(['status' => true]);
    $user->givePermissionTo(SystemPermission::AdminAccess->value);
    $user->givePermissionTo(SystemPermission::ResultsView->value);
    $user->givePermissionTo(SystemPermission::ReportsExport->value);

    $this->actingAs($user);

    $component = Livewire::test(ResultsDashboard::class)
        ->set('budgetEditionId', $edition->id)
        ->call('loadDashboard')
        ->call('publishResultSnapshot')
        ->assertHasNoErrors();

    $publication = ResultPublication::query()->firstOrFail();
    $summary = $component->get('summary');

    expect($publication->budget_edition_id)->toBe($edition->id)
        ->and($publication->published_by_id)->toBe($user->id)
        ->and($publication->project_totals)->toHaveCount(2)
        ->and($summary['latest_publication']['version'])->toBe(1);
});

it('queues administrative report export from results dashboard', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();
    Bus::fake();

    [$edition] = createDashboardResultsFixture();
    $user = User::factory()->create(['status' => true]);
    $user->givePermissionTo(SystemPermission::AdminAccess->value);
    $user->givePermissionTo(SystemPermission::ResultsView->value);
    $user->givePermissionTo(SystemPermission::ReportsExport->value);

    $this->actingAs($user);

    Livewire::test(ResultsDashboard::class)
        ->set('budgetEditionId', $edition->id)
        ->call('queueReportExport', 'admin_vote_cards', 'xlsx')
        ->assertHasNoErrors();

    $export = ReportExport::query()->firstOrFail();

    expect($export->requested_by_id)->toBe($user->id)
        ->and($export->report)->toBe(AdminReportType::AdminVoteCards)
        ->and($export->format)->toBe(ReportExportFormat::Xlsx)
        ->and($export->status)->toBe(ReportExportStatus::Queued)
        ->and($export->context['budget_edition_id'])->toBe($edition->id);

    Bus::assertDispatched(GenerateAdminReportExportJob::class);
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
