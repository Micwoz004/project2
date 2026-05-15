<?php

namespace App\Domain\Reports\Services;

use App\Domain\Projects\Models\Project;
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

    private function legacyFullNumber(Project $project): string
    {
        $symbol = $project->area?->symbol ?: 'BRK';

        return $symbol.'/'.str_pad((string) $project->number, 4, '0', STR_PAD_LEFT);
    }
}
