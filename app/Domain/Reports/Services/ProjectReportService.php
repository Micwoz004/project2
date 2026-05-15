<?php

namespace App\Domain\Reports\Services;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Verification\Models\AdvancedVerification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProjectReportService
{
    public function submittedProjectRows(Carbon|string $from = '2019-07-07 00:00:00'): Collection
    {
        $fromDate = $from instanceof Carbon ? $from : Carbon::parse($from);

        Log::info('project_report.submitted_projects.start', [
            'from' => $fromDate->toDateTimeString(),
        ]);

        $rows = Project::query()
            ->with('area')
            ->where('submitted_at', '>=', $fromDate)
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Project $project) => [
                'project_number' => $this->legacyFullNumber($project),
                'title' => $project->title,
                'submitted_at' => $project->submitted_at?->toDateTimeString(),
            ]);

        Log::info('project_report.submitted_projects.success', [
            'from' => $fromDate->toDateTimeString(),
            'rows_count' => $rows->count(),
        ]);

        return $rows;
    }

    public function unsentAdvancedVerificationRows(string $baseUrl = 'https://sbownioski.szczecin.eu'): Collection
    {
        Log::info('project_report.unsent_advanced_verifications.start');

        $statuses = [
            ProjectStatus::FormallyVerified->value,
            ProjectStatus::RecommendedWjo->value,
            ProjectStatus::RejectedFormally->value,
            ProjectStatus::RejectedWjo->value,
            ProjectStatus::Submitted->value,
        ];

        $rows = AdvancedVerification::query()
            ->with(['project.area', 'department', 'createdBy'])
            ->whereNull('sent_at')
            ->whereHas('project', fn ($query) => $query->whereIn('status', $statuses))
            ->orderBy('project_id')
            ->orderBy('department_id')
            ->get()
            ->map(fn (AdvancedVerification $verification) => [
                'project_number' => $this->legacyFullNumber($verification->project),
                'title' => $verification->project->title,
                'department_name' => $verification->department?->name,
                'author_name' => $verification->createdBy?->name,
                'project_url' => rtrim($baseUrl, '/').'/task/'.($verification->project->legacy_id ?: $verification->project->id),
            ]);

        Log::info('project_report.unsent_advanced_verifications.success', [
            'rows_count' => $rows->count(),
        ]);

        return $rows;
    }

    private function legacyFullNumber(Project $project): string
    {
        $symbol = $project->area?->symbol ?: 'BRK';

        return $symbol.'/'.str_pad((string) $project->number, 4, '0', STR_PAD_LEFT);
    }
}
