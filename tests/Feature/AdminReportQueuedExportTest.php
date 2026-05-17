<?php

use App\Domain\Reports\Actions\QueueAdminReportExportAction;
use App\Domain\Reports\Enums\AdminReportType;
use App\Domain\Reports\Enums\ReportExportFormat;
use App\Domain\Reports\Enums\ReportExportStatus;
use App\Domain\Reports\Jobs\GenerateAdminReportExportJob;
use App\Domain\Reports\Models\ReportExport;
use App\Domain\Reports\Services\AdminReportExportGenerator;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;

function queuedReportFirstXlsxRow(string $xlsx): array
{
    $path = tempnam(sys_get_temp_dir(), 'queued-report-xlsx-');

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

it('queues administrative report exports for report exporters', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();
    Bus::fake();

    $user = User::factory()->create();
    $user->givePermissionTo(SystemPermission::ReportsExport->value);

    $export = app(QueueAdminReportExportAction::class)->execute(
        $user,
        AdminReportType::SubmittedProjects,
        ReportExportFormat::Xlsx,
    );

    expect($export->status)->toBe(ReportExportStatus::Queued)
        ->and($export->requested_by_id)->toBe($user->id)
        ->and($export->report)->toBe(AdminReportType::SubmittedProjects)
        ->and($export->format)->toBe(ReportExportFormat::Xlsx)
        ->and($export->file_name)->toBe('projekty-zlozone.xlsx');

    Bus::assertDispatched(GenerateAdminReportExportJob::class);
});

it('rejects queued administrative report exports without report permission', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $user = User::factory()->create();

    app(QueueAdminReportExportAction::class)->execute(
        $user,
        AdminReportType::SubmittedProjects,
        ReportExportFormat::Csv,
    );
})->throws(DomainException::class, 'Brak uprawnień do zlecenia eksportu raportu.');

it('generates queued administrative xlsx export into local storage', function (): void {
    Storage::fake('local');

    $user = User::factory()->create();
    $export = ReportExport::query()->create([
        'requested_by_id' => $user->id,
        'report' => AdminReportType::SubmittedProjects,
        'format' => ReportExportFormat::Xlsx,
        'status' => ReportExportStatus::Queued,
        'file_name' => AdminReportType::SubmittedProjects->fileName(ReportExportFormat::Xlsx),
        'context' => [],
    ]);

    (new GenerateAdminReportExportJob($export->id))->handle(app(AdminReportExportGenerator::class));

    $export->refresh();

    expect($export->status)->toBe(ReportExportStatus::Completed)
        ->and($export->storage_path)->not->toBeNull()
        ->and($export->completed_at)->not->toBeNull();

    Storage::disk('local')->assertExists($export->storage_path);
    expect(queuedReportFirstXlsxRow(Storage::disk('local')->get($export->storage_path)))->toBe([
        'Numer wniosku',
        'Tytuł',
        'Data złożenia',
    ]);
});
