<?php

namespace App\Domain\Projects\Services;

use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use DomainException;
use Illuminate\Support\Facades\Log;

class ProjectCostLimitService
{
    public const LEGACY_LOCAL_PROJECT = 1;

    public const LEGACY_GREEN_SBO = 2;

    private const LEGACY_SMALL_PROJECT_LIMIT = 10000.0;

    public function resolveSubmissionArea(ProjectArea $requestedArea, int $local): ProjectArea
    {
        if ($local !== self::LEGACY_GREEN_SBO) {
            return $requestedArea;
        }

        $citywideArea = ProjectArea::query()
            ->where('is_local', false)
            ->orWhere('symbol', 'OGM')
            ->orderBy('id')
            ->first();

        if (! $citywideArea instanceof ProjectArea) {
            Log::info('project.cost_limit.green_area_fallback', [
                'requested_area_id' => $requestedArea->id,
            ]);

            return $requestedArea;
        }

        Log::info('project.cost_limit.green_area_resolved', [
            'requested_area_id' => $requestedArea->id,
            'resolved_area_id' => $citywideArea->id,
        ]);

        return $citywideArea;
    }

    public function assertWithinLimit(Project $project): void
    {
        Log::info('project.cost_limit.check.start', [
            'project_id' => $project->id,
            'project_area_id' => $project->project_area_id,
        ]);

        $limit = $this->limitFor($project);

        if ($limit <= 0.0) {
            Log::info('project.cost_limit.skipped_no_limit', [
                'project_id' => $project->id,
                'project_area_id' => $project->project_area_id,
            ]);

            return;
        }

        $cost = (float) $project->costItems()->sum('amount');

        if ($cost <= $limit) {
            Log::info('project.cost_limit.check.success', [
                'project_id' => $project->id,
                'project_area_id' => $project->project_area_id,
                'cost' => $cost,
                'limit' => $limit,
            ]);

            return;
        }

        Log::warning('project.cost_limit.rejected_exceeded', [
            'project_id' => $project->id,
            'project_area_id' => $project->project_area_id,
            'cost' => $cost,
            'limit' => $limit,
        ]);

        throw new DomainException('Koszt projektu przekracza limit kosztów dla wybranego obszaru.');
    }

    public function limitFor(Project $project): float
    {
        $area = $this->areaForLimit($project);

        if (! $area instanceof ProjectArea) {
            return 0.0;
        }

        if ($project->small) {
            $smallLimit = (float) $area->cost_limit_small;

            return $smallLimit > 0.0 ? $smallLimit : self::LEGACY_SMALL_PROJECT_LIMIT;
        }

        $baseLimit = (float) $area->cost_limit;

        if ($baseLimit > 0.0) {
            return $baseLimit;
        }

        return (float) $area->cost_limit_big;
    }

    private function areaForLimit(Project $project): ?ProjectArea
    {
        if ((int) $project->local !== self::LEGACY_GREEN_SBO) {
            return $project->area;
        }

        return ProjectArea::query()
            ->where('is_local', false)
            ->orWhere('symbol', 'OGM')
            ->orderBy('id')
            ->first() ?? $project->area;
    }
}
