<?php

use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Models\User;
use OpenSpout\Reader\XLSX\Reader;

function adminReportFirstXlsxRow(string $xlsx): array
{
    $path = tempnam(sys_get_temp_dir(), 'admin-report-xlsx-');

    if ($path === false) {
        throw new RuntimeException('Nie udało się utworzyć pliku tymczasowego XLSX w teście.');
    }

    file_put_contents($path, $xlsx);

    $reader = new Reader;
    $reader->open($path);

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $reader->close();
            unlink($path);

            return $row->toArray();
        }
    }

    $reader->close();
    unlink($path);

    return [];
}

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

it('exports administrative reports as xlsx from the same csv data source', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $edition = budgetEdition();
    $reportExporter = User::factory()->create();
    $reportExporter->givePermissionTo(SystemPermission::ReportsExport->value);

    $response = $this->actingAs($reportExporter)
        ->get(route('admin.reports.submitted-projects.xlsx'));

    $response->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    expect(adminReportFirstXlsxRow($response->streamedContent()))->toBe([
        'Numer wniosku',
        'Tytuł',
        'Data złożenia',
    ]);

    $xlsxRoutes = [
        route('admin.reports.vote-cards.xlsx', $edition),
        route('admin.reports.unsent-advanced-verifications.xlsx'),
        route('admin.reports.project-corrections.xlsx'),
        route('admin.reports.project-history.xlsx'),
        route('admin.reports.verification-manifest.xlsx'),
        route('admin.reports.category-comparison.xlsx', $edition),
    ];

    foreach ($xlsxRoutes as $route) {
        $this->actingAs($reportExporter)
            ->get($route)
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
});
