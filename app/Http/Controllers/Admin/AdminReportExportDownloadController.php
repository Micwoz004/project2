<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Reports\Enums\ReportExportStatus;
use App\Domain\Reports\Models\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportExportDownloadController extends Controller
{
    public function __invoke(ReportExport $reportExport): StreamedResponse
    {
        Log::info('admin_report_export.download.start', [
            'user_id' => Auth::id(),
            'report_export_id' => $reportExport->id,
        ]);

        if (! Auth::user() instanceof User || Gate::denies('export-reports')) {
            Log::warning('admin_report_export.download.rejected_permission', [
                'user_id' => Auth::id(),
                'report_export_id' => $reportExport->id,
            ]);

            abort(403);
        }

        if ($reportExport->status !== ReportExportStatus::Completed || $reportExport->storage_path === null) {
            Log::warning('admin_report_export.download.rejected_not_ready', [
                'user_id' => Auth::id(),
                'report_export_id' => $reportExport->id,
                'status' => $reportExport->status->value,
            ]);

            abort(404);
        }

        if (! Storage::disk('local')->exists($reportExport->storage_path)) {
            Log::warning('admin_report_export.download.rejected_missing_file', [
                'user_id' => Auth::id(),
                'report_export_id' => $reportExport->id,
            ]);

            abort(404);
        }

        Log::info('admin_report_export.download.success', [
            'user_id' => Auth::id(),
            'report_export_id' => $reportExport->id,
        ]);

        return Storage::disk('local')->download($reportExport->storage_path, $reportExport->file_name);
    }
}
