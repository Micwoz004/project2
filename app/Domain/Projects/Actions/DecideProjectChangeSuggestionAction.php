<?php

namespace App\Domain\Projects\Actions;

use App\Domain\Files\Models\ProjectFile;
use App\Domain\Projects\Enums\ProjectChangeSuggestionDecision;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Models\ProjectChangeSuggestion;
use App\Domain\Projects\Models\ProjectCostItem;
use App\Models\User;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DecideProjectChangeSuggestionAction
{
    public function execute(
        ProjectChangeSuggestion $suggestion,
        ProjectChangeSuggestionDecision $decision,
        User $actor,
    ): ProjectChangeSuggestion {
        Log::info('project.change_suggestion.decide.start', [
            'project_id' => $suggestion->project_id,
            'suggestion_id' => $suggestion->id,
            'decision' => $decision->value,
            'actor_id' => $actor->id,
        ]);

        $currentDecision = $suggestion->decision ?? ProjectChangeSuggestionDecision::Pending;

        if ($currentDecision !== ProjectChangeSuggestionDecision::Pending) {
            Log::warning('project.change_suggestion.decide.rejected_already_decided', [
                'project_id' => $suggestion->project_id,
                'suggestion_id' => $suggestion->id,
                'current_decision' => $currentDecision->value,
            ]);

            throw new DomainException('Propozycja zmian została już rozstrzygnięta.');
        }

        return DB::transaction(function () use ($suggestion, $decision, $actor): ProjectChangeSuggestion {
            $freshSuggestion = ProjectChangeSuggestion::query()->lockForUpdate()->findOrFail($suggestion->id);
            $project = $freshSuggestion->project()->lockForUpdate()->firstOrFail();

            $freshSuggestion->forceFill([
                'decision' => $decision,
                'decision_by_id' => $actor->id,
                'decision_at' => now(),
                'is_accepted_by_admin' => true,
            ])->save();

            if ($decision === ProjectChangeSuggestionDecision::Accepted) {
                $this->applyAcceptedSuggestion($freshSuggestion);
            } else {
                $project->forceFill([
                    'status' => ProjectStatus::DuringMeritVerification,
                ])->save();
            }

            Log::info('project.change_suggestion.decide.success', [
                'project_id' => $freshSuggestion->project_id,
                'suggestion_id' => $freshSuggestion->id,
                'decision' => $decision->value,
            ]);

            return $freshSuggestion->refresh();
        });
    }

    private function applyAcceptedSuggestion(ProjectChangeSuggestion $suggestion): void
    {
        $project = $suggestion->project;
        $newData = $suggestion->new_data;

        $project->forceFill([
            'title' => Arr::get($newData, 'title', $project->title),
            'local' => Arr::get($newData, 'local', $project->local),
            'project_area_id' => $this->projectAreaId($newData, $project->project_area_id),
            'argumentation' => Arr::get($newData, 'argumentation', $project->argumentation),
            'localization' => Arr::get($newData, 'localization', $project->localization),
            'map_data' => Arr::get($newData, 'mapData', Arr::get($newData, 'map_data', $project->map_data)),
            'goal' => Arr::get($newData, 'goal', $project->goal),
            'description' => Arr::get($newData, 'description', $project->description),
            'availability' => Arr::get($newData, 'availability', $project->availability),
            'free_of_charge' => Arr::get($newData, 'freeOfCharge', Arr::get($newData, 'free_of_charge', $project->free_of_charge)),
            'recipients' => Arr::get($newData, 'recipients', $project->recipients),
            'status' => ProjectStatus::ChangesSuggestionAccepted,
        ])->save();

        ProjectCostItem::query()->where('project_id', $project->id)->delete();

        foreach ($suggestion->new_costs as $cost) {
            $project->costItems()->create([
                'description' => Arr::get($cost, 'description', ''),
                'amount' => Arr::get($cost, 'sum', Arr::get($cost, 'amount', 0)),
            ]);
        }

        foreach ($suggestion->new_files as $fileData) {
            $file = ProjectFile::query()
                ->where('project_id', $project->id)
                ->where('legacy_id', Arr::get($fileData, 'id'))
                ->first();

            if ($file instanceof ProjectFile) {
                $file->forceFill([
                    'description' => Arr::get($fileData, 'description', $file->description),
                ])->save();
            }
        }
    }

    /**
     * @param  array<string, mixed>  $newData
     */
    private function projectAreaId(array $newData, ?int $fallback): ?int
    {
        if (Arr::has($newData, 'project_area_id')) {
            return (int) Arr::get($newData, 'project_area_id');
        }

        if (Arr::has($newData, 'taskTypeId')) {
            return ProjectArea::query()->where('legacy_id', (int) Arr::get($newData, 'taskTypeId'))->value('id') ?? $fallback;
        }

        return $fallback;
    }
}
