<?php

namespace App\Products\CivicBudget\Http\Resources\Mobile;

use App\Products\CivicBudget\Domain\BudgetEditions\Enums\BudgetEditionState;
use App\Products\CivicBudget\Domain\BudgetEditions\Models\BudgetEdition;
use App\Products\CivicBudget\Domain\Files\Models\ProjectFile;
use App\Products\CivicBudget\Domain\Projects\Enums\ProjectStatus;
use App\Products\CivicBudget\Domain\Projects\Models\Category;
use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Products\CivicBudget\Domain\Projects\Models\ProjectArea;
use App\Models\User;
use Illuminate\Support\Str;

class MobileCivicBudgetPayload
{
    public function user(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'displayName' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'street' => $user->street,
            'houseNo' => $user->house_no,
            'flatNo' => $user->flat_no,
            'postCode' => $user->post_code,
            'city' => $user->city,
            'emailVerified' => $user->email_verified_at !== null,
        ];
    }

    public function edition(BudgetEdition $edition, BudgetEditionState $state): array
    {
        $year = (int) ($edition->year ?: $edition->propose_start->format('Y'));

        return [
            'id' => (string) $edition->id,
            'name' => $edition->name ?: 'Budżet Obywatelski '.$year,
            'year' => $year,
            'status' => $state === BudgetEditionState::Inactive ? 'archived' : 'active',
            'summary' => $this->editionSummary($state),
            'description' => 'Moduł mieszkańca Budżetu Obywatelskiego: projekty, zgłoszenia i obsługa konta.',
            'heroImageUrl' => url('/images/public-spa/civic-projects-illustration.png'),
            'heroImageAlt' => 'Mieszkańcy planujący projekty miejskie',
            'startsAt' => $edition->propose_start->toISOString(),
            'endsAt' => $edition->result_announcement_end->toISOString(),
            'timeline' => $this->timeline($edition, $state),
            'projectCount' => Project::query()->where('budget_edition_id', $edition->id)->count(),
            'locationCount' => ProjectArea::query()->count(),
            'totalBudget' => (float) ProjectArea::query()->sum('cost_limit'),
        ];
    }

    public function settings(BudgetEdition $edition, BudgetEditionState $state): array
    {
        return [
            'editionId' => (string) $edition->id,
            'submissionOpen' => $state === BudgetEditionState::Propose,
            'votingOpen' => $state === BudgetEditionState::Voting,
            'allowMultipleProjects' => true,
            'maxProjectsPerVote' => 2,
            'showPetitionsPublicly' => true,
            'showProjectCosts' => true,
            'allowPetitionAttachments' => true,
        ];
    }

    public function area(ProjectArea $area): array
    {
        return [
            'id' => (string) $area->id,
            'name' => $area->name,
            'district' => $area->name_shortcut ?: $area->symbol,
        ];
    }

    public function category(Category $category, ?BudgetEdition $edition = null): array
    {
        return [
            'id' => (string) $category->id,
            'editionId' => (string) ($edition?->id ?? ''),
            'name' => $category->name,
            'description' => 'Kategoria projektu w Budżecie Obywatelskim.',
            'budgetMin' => 0,
            'budgetMax' => 0,
        ];
    }

    public function project(Project $project): array
    {
        $cost = (float) ($project->cost_formatted ?? $project->costItems->sum('amount'));

        return [
            'id' => (string) $project->id,
            'editionId' => (string) $project->budget_edition_id,
            'petitionTypeId' => (string) ($project->category_id ?? $project->categories->first()?->id ?? ''),
            'locationId' => (string) ($project->project_area_id ?? ''),
            'name' => $project->title,
            'summary' => $project->short_description ?: Str::limit((string) $project->description, 180),
            'description' => $project->description ?: '',
            'budget' => $cost,
            'status' => $this->publicProjectStatus($project->status),
            'canVote' => $project->status === ProjectStatus::Picked,
            'number' => (string) ($project->number_drawn ?? $project->number ?? $project->id),
            'voteGroup' => $project->area?->is_local ? 'local' : 'citywide',
            'district' => $project->area?->name ?? 'Całe miasto',
            'imageUrl' => url('/images/public-spa/civic-projects-illustration.png'),
            'imageAlt' => 'Projekt Budżetu Obywatelskiego',
            'tags' => array_values(array_filter([
                $project->area?->name,
                $project->category?->name ?? $project->categories->first()?->name,
                $project->publicStatusLabel(),
            ])),
            'attachments' => $this->attachments($project),
        ];
    }

    public function votingProject(Project $project, string $voteGroup): array
    {
        return [
            ...$this->project($project),
            'voteGroup' => $voteGroup,
        ];
    }

    public function residentProject(Project $project): array
    {
        return [
            ...$this->project($project),
            'status' => $this->residentProjectStatus($project),
            'submittedAt' => ($project->submitted_at ?? $project->created_at)->toISOString(),
            'localization' => $project->localization,
            'goal' => $project->goal,
            'argumentation' => $project->argumentation,
            'availability' => $project->availability,
            'recipients' => $project->recipients,
            'freeOfCharge' => $project->free_of_charge,
            'correction' => $this->activeCorrection($project),
        ];
    }

    private function editionSummary(BudgetEditionState $state): string
    {
        return match ($state) {
            BudgetEditionState::Propose => 'Trwa nabór projektów.',
            BudgetEditionState::Voting => 'Trwa głosowanie mieszkańców.',
            BudgetEditionState::ResultAnnouncement => 'Wyniki są publikowane.',
            BudgetEditionState::Inactive => 'Edycja jest nieaktywna.',
            default => 'Trwa obsługa bieżącej edycji.',
        };
    }

    private function timeline(BudgetEdition $edition, BudgetEditionState $state): array
    {
        return [
            $this->timelineStep('submission', 'Zgłaszanie projektów', BudgetEditionState::Propose, $state, $edition->propose_start, $edition->propose_end),
            $this->timelineStep('verification', 'Weryfikacja', BudgetEditionState::PreVotingVerification, $state, $edition->propose_end, $edition->pre_voting_verification_end),
            $this->timelineStep('voting', 'Głosowanie', BudgetEditionState::Voting, $state, $edition->voting_start, $edition->voting_end),
            $this->timelineStep('results', 'Wyniki', BudgetEditionState::ResultAnnouncement, $state, $edition->voting_end, $edition->result_announcement_end),
        ];
    }

    private function timelineStep(string $key, string $label, BudgetEditionState $step, BudgetEditionState $state, mixed $startsAt, mixed $endsAt): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => $this->timelineStatus($step, $state),
            'startsAt' => $startsAt->toISOString(),
            'endsAt' => $endsAt->toISOString(),
        ];
    }

    private function timelineStatus(BudgetEditionState $step, BudgetEditionState $state): string
    {
        $order = [
            BudgetEditionState::Propose->value => 1,
            BudgetEditionState::PreVotingVerification->value => 2,
            BudgetEditionState::PreVotingCorrection->value => 3,
            BudgetEditionState::Voting->value => 4,
            BudgetEditionState::PostVotingVerification->value => 5,
            BudgetEditionState::ResultAnnouncement->value => 6,
            BudgetEditionState::Inactive->value => 0,
        ];

        if ($step === $state) {
            return 'active';
        }

        return $order[$step->value] < $order[$state->value] ? 'done' : 'upcoming';
    }

    private function publicProjectStatus(ProjectStatus $status): string
    {
        if ($status === ProjectStatus::Picked) {
            return 'in-voting';
        }

        if ($status === ProjectStatus::PickedForRealization) {
            return 'winner';
        }

        if ($status->isRejected()) {
            return 'rejected';
        }

        return in_array($status, [ProjectStatus::TeamAccepted, ProjectStatus::MeritVerificationAccepted], true)
            ? 'approved'
            : 'submitted';
    }

    private function residentProjectStatus(Project $project): string
    {
        if ($this->activeCorrection($project) !== null) {
            return 'returned';
        }

        if ($project->status === ProjectStatus::WorkingCopy) {
            return 'draft';
        }

        if (in_array($project->status, [ProjectStatus::Picked, ProjectStatus::TeamAccepted, ProjectStatus::MeritVerificationAccepted], true)) {
            return 'approved';
        }

        return $project->status->isRejected() ? 'returned' : 'submitted';
    }

    private function attachments(Project $project): array
    {
        return $project->publicFiles
            ->map(fn (ProjectFile $file): array => [
                'id' => (string) $file->id,
                'title' => $file->original_name,
                'url' => $file->publicUrl(),
            ])
            ->filter(fn (array $file): bool => is_string($file['url']) && $file['url'] !== '')
            ->values()
            ->all();
    }

    private function activeCorrection(Project $project): ?array
    {
        $correction = $project->corrections
            ->filter(fn ($correction): bool => ! $correction->correction_done && $correction->correction_deadline?->isFuture())
            ->sortByDesc('correction_deadline')
            ->first();

        if (! $correction) {
            return null;
        }

        return [
            'deadline' => $correction->correction_deadline?->toISOString(),
            'notes' => $correction->notes,
            'allowedFields' => $correction->allowed_fields,
        ];
    }
}
