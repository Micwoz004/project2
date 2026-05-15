<?php

use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Domain\Users\Enums\SystemRole;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Models\Voter;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('synchronizes legacy roles and canonical permissions', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    expect(Role::query()->where('name', SystemRole::Admin->value)->exists())->toBeTrue()
        ->and(Role::query()->where('name', SystemRole::AnalystOds->value)->exists())->toBeTrue()
        ->and(Role::query()->where('name', SystemRole::VerifierZod->value)->exists())->toBeTrue()
        ->and(Permission::query()->where('name', SystemPermission::AdminAccess->value)->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'manage task groups')->exists())->toBeTrue()
        ->and(Role::findByName(SystemRole::Admin->value)->hasPermissionTo(SystemPermission::UsersManage->value))->toBeTrue()
        ->and(Role::findByName(SystemRole::Applicant->value)->hasPermissionTo(SystemPermission::AdminAccess->value))->toBeFalse();
});

it('allows only active administrative users to access the Filament panel', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole(SystemRole::Admin->value);

    $inactiveAdmin = User::factory()->create(['status' => false]);
    $inactiveAdmin->assignRole(SystemRole::Admin->value);

    $applicant = User::factory()->create(['status' => true]);
    $applicant->assignRole(SystemRole::Applicant->value);

    $panel = Filament::getPanel('admin');

    expect($admin->canAccessPanel($panel))->toBeTrue()
        ->and($inactiveAdmin->canAccessPanel($panel))->toBeFalse()
        ->and($applicant->canAccessPanel($panel))->toBeFalse();
});

it('gates budget editions and dictionaries with dedicated permissions', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $admin = User::factory()->create();
    $admin->assignRole(SystemRole::Admin->value);

    $dictionaryManager = User::factory()->create();
    $dictionaryManager->givePermissionTo(SystemPermission::DictionariesManage->value);

    $applicant = User::factory()->create();
    $applicant->assignRole(SystemRole::Applicant->value);

    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $category = Category::query()->create(['name' => 'Zieleń']);

    expect(Gate::forUser($admin)->allows('update', $edition))->toBeTrue()
        ->and(Gate::forUser($dictionaryManager)->allows('update', $area))->toBeTrue()
        ->and(Gate::forUser($dictionaryManager)->allows('update', $category))->toBeTrue()
        ->and(Gate::forUser($applicant)->allows('update', $edition))->toBeFalse()
        ->and(Gate::forUser($applicant)->allows('update', $area))->toBeFalse()
        ->and(Gate::forUser($applicant)->allows('update', $category))->toBeFalse();
});

it('gates vote cards results and report exports with dedicated permissions', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $voteCardManager = User::factory()->create();
    $voteCardManager->givePermissionTo(SystemPermission::VoteCardsManage->value);

    $resultsViewer = User::factory()->create();
    $resultsViewer->givePermissionTo(SystemPermission::ResultsView->value);

    $reportExporter = User::factory()->create();
    $reportExporter->givePermissionTo(SystemPermission::ReportsExport->value);

    $applicant = User::factory()->create();
    $applicant->assignRole(SystemRole::Applicant->value);

    $edition = budgetEdition();
    $voter = Voter::query()->create([
        'pesel' => '44051401458',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
    ]);
    $voteCard = VoteCard::query()->create([
        'budget_edition_id' => $edition->id,
        'voter_id' => $voter->id,
        'status' => VoteCardStatus::Verifying,
    ]);

    expect(Gate::forUser($voteCardManager)->allows('update', $voteCard))->toBeTrue()
        ->and(Gate::forUser($applicant)->allows('update', $voteCard))->toBeFalse()
        ->and(Gate::forUser($resultsViewer)->allows('view-results'))->toBeTrue()
        ->and(Gate::forUser($applicant)->allows('view-results'))->toBeFalse()
        ->and(Gate::forUser($reportExporter)->allows('export-reports'))->toBeTrue()
        ->and(Gate::forUser($applicant)->allows('export-reports'))->toBeFalse();
});
