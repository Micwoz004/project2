<?php

namespace App\Domain\Reports\Services;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Models\ProjectCorrection;
use App\Domain\Projects\Models\ProjectVersion;
use App\Domain\Verification\Enums\VerificationCardStatus;
use App\Domain\Verification\Models\AdvancedVerification;
use Illuminate\Support\Arr;
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

    public function projectHistoryRows(): Collection
    {
        Log::info('project_report.project_history.start');

        $areasByLegacyId = ProjectArea::query()
            ->whereNotNull('legacy_id')
            ->get()
            ->keyBy('legacy_id');

        $rows = ProjectVersion::query()
            ->with(['project.area', 'user'])
            ->whereHas('project')
            ->orderBy('id')
            ->get()
            ->map(fn (ProjectVersion $version) => [
                'project_id' => $version->project->legacy_id ?: $version->project->id,
                'project_number' => $this->legacyFullNumber($version->project),
                'title' => $this->historyValue($version->data, 'title'),
                'project_category' => $this->historyProjectCategory($version->data),
                'district' => $this->historyDistrict($version->data, $areasByLegacyId),
                'category_reason' => $this->historyValue($version->data, 'argumentation'),
                'localization' => $this->historyValue($version->data, 'localization'),
                'goal' => $this->historyValue($version->data, 'goal'),
                'description' => $this->historyValue($version->data, 'description'),
                'recipients' => $this->historyValue($version->data, 'recipients'),
                'free_of_charge' => $this->historyValue($version->data, 'freeOfCharge'),
                'status' => $this->historyStatus($version->data),
                'changed_at' => $version->created_at?->toDateTimeString(),
                'changed_by' => $version->user?->name ?: '---',
            ]);

        Log::info('project_report.project_history.success', [
            'rows_count' => $rows->count(),
        ]);

        return $rows;
    }

    public function verificationResultManifestRows(): Collection
    {
        Log::info('project_report.verification_result_manifest.start');

        $rows = Project::query()
            ->with([
                'area',
                'formalVerifications',
                'initialMeritVerifications',
                'finalMeritVerifications',
                'consultationVerifications',
            ])
            ->whereNotIn('status', [
                ProjectStatus::WorkingCopy->value,
                ProjectStatus::Revoked->value,
            ])
            ->orderBy('id')
            ->get()
            ->filter(fn (Project $project) => $this->hasVerificationResultPayload($project))
            ->map(fn (Project $project) => [
                'project_id' => $project->legacy_id ?: $project->id,
                'project_number' => $this->legacyFullNumber($project),
                'title' => $project->title,
                'formal_present' => $project->formalVerifications->isNotEmpty() ? 1 : 0,
                'initial_sent_count' => $this->sentVerificationCount($project->initialMeritVerifications),
                'final_sent_count' => $this->sentVerificationCount($project->finalMeritVerifications),
                'consultation_sent_count' => $this->sentVerificationCount($project->consultationVerifications),
                'file_name' => $this->verificationResultFileName($project),
            ])
            ->values();

        Log::info('project_report.verification_result_manifest.success', [
            'rows_count' => $rows->count(),
        ]);

        return $rows;
    }

    private function correctionFieldAllowed(ProjectCorrection $correction, string $field): int
    {
        return in_array($field, $correction->allowed_fields, true) ? 1 : 0;
    }

    private function historyValue(array $data, string $key): mixed
    {
        $value = Arr::get($data, $key);

        return $value === null || $value === '' ? '---' : $value;
    }

    private function historyProjectCategory(array $data): string
    {
        return match ((int) Arr::get($data, 'local', 0)) {
            1 => 'Projekt lokalny',
            2 => 'Projekt Zielonego SBO',
            default => '',
        };
    }

    private function historyDistrict(array $data, Collection $areasByLegacyId): string
    {
        $taskTypeId = Arr::get($data, 'taskTypeId');

        if ($taskTypeId === null || $taskTypeId === '' || (int) $taskTypeId === 35) {
            return '---';
        }

        return $areasByLegacyId->get((int) $taskTypeId)?->name ?: '---';
    }

    private function historyStatus(array $data): string
    {
        $status = Arr::get($data, 'status');

        if ($status === null || $status === '') {
            return '---';
        }

        return ProjectStatus::tryFrom((int) $status)?->publicLabel() ?: '---';
    }

    private function hasVerificationResultPayload(Project $project): bool
    {
        return $project->formalVerifications->isNotEmpty()
            || $this->sentVerificationCount($project->initialMeritVerifications) > 0
            || $this->sentVerificationCount($project->finalMeritVerifications) > 0
            || $this->sentVerificationCount($project->consultationVerifications) > 0;
    }

    private function sentVerificationCount(Collection $verifications): int
    {
        return $verifications
            ->filter(fn ($verification) => $verification->status === VerificationCardStatus::Sent)
            ->count();
    }

    private function verificationResultFileName(Project $project): string
    {
        return str_replace('/', '_', $this->legacyFullNumber($project)).'_'.($project->legacy_id ?: $project->id).'/karta_weryfikacji.pdf';
    }

    private function legacyFullNumber(Project $project): string
    {
        $symbol = $project->area?->symbol ?: 'BRK';

        return $symbol.'/'.str_pad((string) $project->number, 4, '0', STR_PAD_LEFT);
    }
}
