<?php

namespace App\Domain\Reports\Actions;

use App\Domain\Reports\Enums\AdminReportType;
use App\Domain\Reports\Enums\ReportExportFormat;
use App\Domain\Reports\Enums\ReportExportStatus;
use App\Domain\Reports\Jobs\GenerateAdminReportExportJob;
use App\Domain\Reports\Models\ReportExport;
use App\Domain\Users\Enums\SystemPermission;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Log;

class QueueAdminReportExportAction
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function execute(User $user, AdminReportType $report, ReportExportFormat $format, array $context = []): ReportExport
    {
        Log::info('admin_report_export.queue.start', [
            'user_id' => $user->id,
            'report' => $report->value,
            'format' => $format->value,
        ]);

        if (! $this->canExportReports($user)) {
            Log::warning('admin_report_export.queue.rejected_permission', [
                'user_id' => $user->id,
                'report' => $report->value,
            ]);

            throw new DomainException('Brak uprawnień do zlecenia eksportu raportu.');
        }

        if ($report->requiresBudgetEdition() && (int) ($context['budget_edition_id'] ?? 0) < 1) {
            Log::warning('admin_report_export.queue.rejected_missing_budget_edition', [
                'user_id' => $user->id,
                'report' => $report->value,
            ]);

            throw new DomainException('Raport wymaga identyfikatora edycji SBO.');
        }

        $export = ReportExport::query()->create([
            'requested_by_id' => $user->id,
            'report' => $report,
            'format' => $format,
            'status' => ReportExportStatus::Queued,
            'file_name' => $report->fileName($format),
            'context' => $context,
        ]);

        GenerateAdminReportExportJob::dispatch($export->id);

        Log::info('admin_report_export.queue.success', [
            'user_id' => $user->id,
            'report_export_id' => $export->id,
            'report' => $report->value,
            'format' => $format->value,
        ]);

        return $export;
    }

    private function canExportReports(User $user): bool
    {
        return $user->can(SystemPermission::ReportsExport->value)
            || $user->hasAnyRole(['admin', 'bdo']);
    }
}
