<?php

namespace App\Domain\Projects\Actions;

use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectCorrection;
use App\Domain\Projects\Services\ProjectLifecycleService;
use App\Domain\Projects\Services\ProjectSubmissionValidator;
use App\Models\User;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplyCorrectionAction
{
    public function __construct(
        private readonly ProjectLifecycleService $lifecycle,
        private readonly ProjectSubmissionValidator $validator,
        private readonly RecordProjectVersionAction $recordProjectVersion,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Project $project, User $actor, array $attributes): Project
    {
        Log::info('project.correction.apply.start', [
            'project_id' => $project->id,
            'actor_id' => $actor->id,
            'status' => $project->status->value,
        ]);

        if (! $this->lifecycle->canApplicantEdit($project)) {
            Log::warning('project.correction.apply.rejected_closed_window', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
            ]);

            throw new DomainException('Projekt nie jest w aktywnym oknie korekty.');
        }

        $correction = $this->activeCorrection($project);
        $allowedAttributes = $this->filterAllowedAttributes($attributes, $correction);
        $costItems = $this->filterAllowedCostItems($attributes, $correction);
        $attachmentsChanged = $this->attachmentsChanged($attributes, $correction);

        if ($allowedAttributes === [] && $costItems === null && ! $attachmentsChanged) {
            Log::warning('project.correction.apply.rejected_no_allowed_fields', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'correction_id' => $correction->id,
            ]);

            throw new DomainException('Przekazane pola nie są dopuszczone do korekty.');
        }

        return DB::transaction(function () use ($project, $actor, $correction, $allowedAttributes, $costItems): Project {
            $project->forceFill($allowedAttributes)->save();
            if (array_key_exists(ProjectCorrectionField::Category->value, $allowedAttributes) && $allowedAttributes[ProjectCorrectionField::Category->value] !== null) {
                $project->categories()->sync([$allowedAttributes[ProjectCorrectionField::Category->value]]);
            }

            if ($costItems !== null) {
                $this->replaceCostItems($project, $costItems);
            }

            $this->validator->assertCanSubmit($project);

            $correction->forceFill([
                'correction_done' => true,
            ])->save();

            $project->forceFill([
                'need_correction' => false,
                'correction_start_time' => null,
                'correction_end_time' => null,
            ])->save();

            $this->recordProjectVersion->execute($project, $actor);

            Log::info('project.correction.apply.success', [
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'correction_id' => $correction->id,
                'status' => $project->status->value,
            ]);

            return $project->refresh();
        });
    }

    private function activeCorrection(Project $project): ProjectCorrection
    {
        $correction = $project->corrections()
            ->where('correction_done', false)
            ->where('correction_deadline', '>', Carbon::now())
            ->latest()
            ->first();

        if (! $correction instanceof ProjectCorrection) {
            Log::warning('project.correction.apply.rejected_missing_correction', [
                'project_id' => $project->id,
            ]);

            throw new DomainException('Nie znaleziono aktywnej korekty.');
        }

        return $correction;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function filterAllowedAttributes(array $attributes, ProjectCorrection $correction): array
    {
        $allowedColumns = array_intersect(
            ProjectCorrectionField::editableProjectColumns(),
            $correction->allowed_fields,
        );

        return array_intersect_key($attributes, array_flip($allowedColumns));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function attachmentsChanged(array $attributes, ProjectCorrection $correction): bool
    {
        if (($attributes['attachments_changed'] ?? false) !== true) {
            return false;
        }

        return array_intersect($correction->allowed_fields, [
            ProjectCorrectionField::SupportAttachment->value,
            ProjectCorrectionField::AgreementAttachment->value,
            ProjectCorrectionField::MapAttachment->value,
            ProjectCorrectionField::ParentAgreementAttachment->value,
            ProjectCorrectionField::Attachments->value,
        ]) !== [];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<array{description: string, amount: float}>|null
     */
    private function filterAllowedCostItems(array $attributes, ProjectCorrection $correction): ?array
    {
        if (! in_array(ProjectCorrectionField::Cost->value, $correction->allowed_fields, true) || ! array_key_exists('cost_items', $attributes)) {
            return null;
        }

        return collect($attributes['cost_items'])
            ->map(fn (array $item): array => [
                'description' => trim((string) ($item['description'] ?? '')),
                'amount' => (float) ($item['amount'] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array{description: string, amount: float}>  $costItems
     */
    private function replaceCostItems(Project $project, array $costItems): void
    {
        if ($costItems === []) {
            Log::warning('project.correction.apply.rejected_missing_cost_items', [
                'project_id' => $project->id,
            ]);

            throw new DomainException('Projekt musi mieć co najmniej jedną pozycję kosztorysu.');
        }

        foreach ($costItems as $costItem) {
            if ($costItem['description'] === '' || $costItem['amount'] < 0) {
                Log::warning('project.correction.apply.rejected_invalid_cost_item', [
                    'project_id' => $project->id,
                ]);

                throw new DomainException('Pozycja kosztorysu wymaga opisu i nieujemnej kwoty.');
            }
        }

        $project->costItems()->delete();

        foreach ($costItems as $costItem) {
            $project->costItems()->create($costItem);
        }

        $project->forceFill([
            'cost' => collect($costItems)
                ->map(fn (array $costItem): string => $costItem['description'].': '.$costItem['amount'])
                ->implode(PHP_EOL),
            'cost_formatted' => collect($costItems)->sum('amount'),
        ])->save();
    }
}
