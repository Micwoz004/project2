<?php

use App\Domain\Reports\Enums\AdminReportType;
use App\Domain\Reports\Enums\ReportExportFormat;
use App\Domain\Reports\Enums\ReportExportStatus;
use App\Domain\Reports\Models\ReportExport;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Filament\Resources\ReportExports\ReportExportResource;
use App\Models\User;

it('shows report export resource only for report exporters', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $reportExporter = User::factory()->create(['status' => true]);
    $reportExporter->givePermissionTo(SystemPermission::ReportsExport->value);
    $guest = User::factory()->create(['status' => true]);

    $this->actingAs($reportExporter);
    expect(ReportExportResource::canViewAny())->toBeTrue();

    $this->actingAs($guest);
    expect(ReportExportResource::canViewAny())->toBeFalse();
});

it('renders report export resource list for admin users with export permission', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $user = User::factory()->create(['status' => true]);
    $user->givePermissionTo(SystemPermission::AdminAccess->value);
    $user->givePermissionTo(SystemPermission::ReportsExport->value);
    ReportExport::query()->create([
        'requested_by_id' => $user->id,
        'report' => AdminReportType::SubmittedProjects,
        'format' => ReportExportFormat::Xlsx,
        'status' => ReportExportStatus::Queued,
        'file_name' => 'projekty-zlozone.xlsx',
        'context' => [],
    ]);

    $this->actingAs($user)
        ->get(ReportExportResource::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('Eksporty Raportów')
        ->assertSee('Projekty złożone')
        ->assertSee('w kolejce');

    expect(array_keys(ReportExportResource::getPages()))->toBe(['index']);
});
