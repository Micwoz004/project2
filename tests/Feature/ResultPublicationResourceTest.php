<?php

use App\Domain\Results\Models\ResultPublication;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Filament\Resources\ResultPublications\ResultPublicationResource;
use App\Models\User;

it('shows result publication snapshots only to result viewers', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $edition = budgetEdition();
    $operator = User::factory()->create(['status' => true]);
    ResultPublication::query()->create([
        'budget_edition_id' => $edition->id,
        'published_by_id' => $operator->id,
        'version' => 3,
        'total_points' => 25,
        'projects_count' => 4,
        'project_totals' => [],
        'area_totals' => [],
        'category_totals' => [],
        'status_counts' => [],
        'tie_groups' => [],
        'category_differences' => [],
        'published_at' => now(),
    ]);

    $viewer = User::factory()->create(['status' => true]);
    $viewer->givePermissionTo(SystemPermission::AdminAccess->value);
    $viewer->givePermissionTo(SystemPermission::ResultsView->value);
    $adminOnly = User::factory()->create(['status' => true]);
    $adminOnly->givePermissionTo(SystemPermission::AdminAccess->value);

    $this->actingAs($viewer)
        ->get(ResultPublicationResource::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('25')
        ->assertSee('3');

    $this->actingAs($adminOnly)
        ->get(ResultPublicationResource::getUrl(panel: 'admin'))
        ->assertForbidden();

    expect(array_keys(ResultPublicationResource::getPages()))->toBe(['index'])
        ->and(ResultPublicationResource::canCreate())->toBeFalse();
});
