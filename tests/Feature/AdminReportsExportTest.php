<?php

use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Models\User;

it('exports admin vote cards csv only for report exporters', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $edition = budgetEdition();
    $reportExporter = User::factory()->create();
    $reportExporter->givePermissionTo(SystemPermission::ReportsExport->value);
    $applicant = User::factory()->create();

    $this->actingAs($applicant)
        ->get(route('admin.reports.vote-cards', $edition))
        ->assertForbidden();

    $response = $this->actingAs($reportExporter)
        ->get(route('admin.reports.vote-cards', $edition));

    $response->assertOk();
    expect($response->streamedContent())->toContain('ID karty');
});

it('exposes baseline administrative csv reports behind report export permission', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $edition = budgetEdition();
    $reportExporter = User::factory()->create();
    $reportExporter->givePermissionTo(SystemPermission::ReportsExport->value);

    $routes = [
        route('admin.reports.submitted-projects'),
        route('admin.reports.unsent-advanced-verifications'),
        route('admin.reports.project-corrections'),
        route('admin.reports.project-history'),
        route('admin.reports.verification-manifest'),
        route('admin.reports.category-comparison', $edition),
    ];

    foreach ($routes as $route) {
        $this->actingAs($reportExporter)
            ->get($route)
            ->assertOk();
    }
});
