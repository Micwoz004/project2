<?php

namespace App\Domain\Reports\Jobs;

use App\Domain\Reports\Enums\ReportExportStatus;
use App\Domain\Reports\Models\ReportExport;
use App\Domain\Reports\Services\AdminReportExportGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateAdminReportExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $reportExportId,
    ) {}

    public function handle(AdminReportExportGenerator $generator): void
    {
        Log::info('admin_report_export.job.start', [
            'report_export_id' => $this->reportExportId,
        ]);

        $export = ReportExport::query()->findOrFail($this->reportExportId);
        $export->forceFill([
            'status' => ReportExportStatus::Processing,
            'error_message' => null,
        ])->save();

        try {
            $content = $generator->generate($export->report, $export->format, $export->context ?? []);
            $path = "report-exports/{$export->id}/{$export->file_name}";

            Storage::disk('local')->put($path, $content);

            $export->forceFill([
                'status' => ReportExportStatus::Completed,
                'storage_path' => $path,
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $throwable) {
            $export->forceFill([
                'status' => ReportExportStatus::Failed,
                'error_message' => mb_substr($throwable->getMessage(), 0, 1000),
            ])->save();

            Log::error('admin_report_export.job.failed', [
                'report_export_id' => $export->id,
                'report' => $export->report->value,
                'format' => $export->format->value,
                'exception' => $throwable,
            ]);

            return;
        }

        Log::info('admin_report_export.job.success', [
            'report_export_id' => $export->id,
            'report' => $export->report->value,
            'format' => $export->format->value,
        ]);
    }
}
