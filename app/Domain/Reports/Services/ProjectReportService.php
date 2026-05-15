<?php

namespace App\Domain\Reports\Services;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectCorrection;
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

    public function projectCorrectionRows(): Collection
    {
        Log::info('project_report.project_corrections.start');

        $rows = ProjectCorrection::query()
            ->orderBy('id')
            ->get()
            ->map(fn (ProjectCorrection $correction) => [
                'title' => $this->correctionFieldAllowed($correction, 'title'),
                'taskTypeId' => $this->correctionFieldAllowed($correction, 'project_area_id'),
                'localization' => $this->correctionFieldAllowed($correction, 'localization'),
                'mapData' => $this->correctionFieldAllowed($correction, 'map_data'),
                'goal' => $this->correctionFieldAllowed($correction, 'goal'),
                'description' => $this->correctionFieldAllowed($correction, 'description'),
                'argumentation' => $this->correctionFieldAllowed($correction, 'argumentation'),
                'availability' => $this->correctionFieldAllowed($correction, 'availability'),
                'recipients' => $this->correctionFieldAllowed($correction, 'recipients'),
                'freeOfCharge' => $this->correctionFieldAllowed($correction, 'free_of_charge'),
                'cost' => $this->correctionFieldAllowed($correction, 'cost'),
                'supportAttachment' => $this->correctionFieldAllowed($correction, 'support_attachment'),
                'agreementAttachment' => $this->correctionFieldAllowed($correction, 'agreement_attachment'),
                'mapAttachment' => $this->correctionFieldAllowed($correction, 'map_attachment'),
                'parentAgreementAttachment' => $this->correctionFieldAllowed($correction, 'parent_agreement_attachment'),
                'attachments' => $this->correctionFieldAllowed($correction, 'attachments'),
                'categoryId' => $this->correctionFieldAllowed($correction, 'category_id'),
                'notes' => $correction->notes,
                'createdAt' => $correction->created_at?->toDateTimeString(),
                'correctionDeadline' => $correction->correction_deadline?->toDateTimeString(),
            ]);

        Log::info('project_report.project_corrections.success', [
            'rows_count' => $rows->count(),
        ]);

        return $rows;
    }

    private function correctionFieldAllowed(ProjectCorrection $correction, string $field): int
    {
        return in_array($field, $correction->allowed_fields, true) ? 1 : 0;
    }

    private function legacyFullNumber(Project $project): string
    {
        $symbol = $project->area?->symbol ?: 'BRK';

        return $symbol.'/'.str_pad((string) $project->number, 4, '0', STR_PAD_LEFT);
    }
}
