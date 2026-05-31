<?php

namespace App\Products\CivicBudget\Http\Controllers\Api\Mobile;

use App\Products\CivicBudget\Domain\BudgetEditions\Enums\BudgetEditionState;
use App\Products\CivicBudget\Domain\BudgetEditions\Models\BudgetEdition;
use App\Products\CivicBudget\Domain\BudgetEditions\Services\BudgetEditionStateResolver;
use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Products\CivicBudget\Domain\Voting\Data\VoterIdentityData;
use App\Products\CivicBudget\Domain\Voting\Enums\CitizenConfirmation;
use App\Products\CivicBudget\Domain\Voting\Services\CastVoteService;
use App\Products\CivicBudget\Domain\Voting\Services\VotingTokenService;
use App\Http\Controllers\Controller;
use App\Products\CivicBudget\Http\Requests\Public\CastPublicVoteRequest;
use App\Products\CivicBudget\Http\Requests\Public\IssueVotingTokenRequest;
use App\Products\CivicBudget\Http\Resources\Mobile\MobileCivicBudgetPayload;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MobileVotingController extends Controller
{
    public function __construct(
        private readonly BudgetEditionStateResolver $stateResolver,
        private readonly MobileCivicBudgetPayload $payload,
    ) {}

    public function show(BudgetEdition $edition): JsonResponse
    {
        Log::info('mobile_voting.show.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $state = $this->stateResolver->resolve($edition);
        $projects = Project::query()
            ->with(['area', 'category', 'categories', 'budgetEdition', 'costItems', 'publicFiles'])
            ->pickedForVoting()
            ->where('budget_edition_id', $edition->id)
            ->where('is_hidden', false)
            ->whereHas('area')
            ->orderByRaw('number_drawn IS NULL')
            ->orderBy('number_drawn')
            ->orderBy('title')
            ->get();

        Log::info('mobile_voting.show.success', [
            'budget_edition_id' => $edition->id,
            'local_projects_count' => $projects->filter(fn (Project $project): bool => (bool) $project->area?->is_local)->count(),
            'citywide_projects_count' => $projects->filter(fn (Project $project): bool => ! (bool) $project->area?->is_local)->count(),
        ]);

        return response()->json([
            'editionId' => (string) $edition->id,
            'votingOpen' => $state === BudgetEditionState::Voting,
            'localLimit' => 1,
            'citywideLimit' => 1,
            'localProjects' => $this->projectsForGroup($projects, true),
            'citywideProjects' => $this->projectsForGroup($projects, false),
            'citizenConfirmOptions' => $this->citizenConfirmOptions(),
        ]);
    }

    public function issueToken(IssueVotingTokenRequest $request, VotingTokenService $votingTokenService): JsonResponse
    {
        Log::info('mobile_voting.issue_token.start', [
            'ip' => $request->ip(),
        ]);

        $data = $request->validated();

        try {
            $votingTokenService->issueSmsToken($this->identityFromData($data, $request->ip(), $request->userAgent()), $data['phone']);
        } catch (DomainException $exception) {
            Log::warning('mobile_voting.issue_token.rejected', [
                'reason' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['token' => [$exception->getMessage()]],
            ], 422);
        }

        Log::info('mobile_voting.issue_token.success', [
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'canProceed' => true,
            'message' => 'Kod SMS został przygotowany.',
        ]);
    }

    public function cast(
        CastPublicVoteRequest $request,
        CastVoteService $castVoteService,
        VotingTokenService $votingTokenService,
    ): JsonResponse {
        Log::info('mobile_voting.cast.start', [
            'ip' => $request->ip(),
        ]);

        $data = $request->validated();
        $edition = BudgetEdition::query()->findOrFail($data['budget_edition_id']);
        $identity = $this->identityFromData($data, $request->ip(), $request->userAgent());

        try {
            $voteCard = $castVoteService->cast(
                $edition,
                $identity,
                array_values(array_filter([$data['local_project_id'] ?? null])),
                array_values(array_filter([$data['citywide_project_id'] ?? null])),
                [
                    'citizen_confirm' => isset($data['citizen_confirm']) ? CitizenConfirmation::from((int) $data['citizen_confirm']) : null,
                    'confirm_missing_category' => (bool) ($data['confirm_missing_category'] ?? false),
                    'parent_name' => $data['parent_name'] ?? null,
                    'parent_confirm' => (bool) ($data['parent_confirm'] ?? false),
                    'voting_token' => $votingTokenService->activateSmsToken($data['phone'], $data['sms_token']),
                ],
            );
        } catch (DomainException $exception) {
            Log::warning('mobile_voting.cast.rejected', [
                'reason' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['vote' => [$exception->getMessage()]],
            ], 422);
        }

        Log::info('mobile_voting.cast.success', [
            'vote_card_id' => $voteCard->id,
        ]);

        return response()->json([
            'confirmationId' => 'SBO-'.$voteCard->id,
            'submittedAt' => $voteCard->created_at->toISOString(),
            'message' => 'Głos został zapisany.',
            'status' => $voteCard->status->label(),
        ]);
    }

    private function projectsForGroup($projects, bool $local): array
    {
        return $projects
            ->filter(fn (Project $project): bool => (bool) $project->area?->is_local === $local)
            ->map(fn (Project $project): array => $this->payload->votingProject($project, $local ? 'local' : 'citywide'))
            ->values()
            ->all();
    }

    private function citizenConfirmOptions(): array
    {
        return collect(CitizenConfirmation::cases())
            ->map(fn (CitizenConfirmation $confirmation): array => [
                'value' => $confirmation->value,
                'label' => match ($confirmation) {
                    CitizenConfirmation::Default => 'Potwierdzam uprawnienie do udziału w głosowaniu',
                    CitizenConfirmation::Living => 'Mieszkam w Szczecinie',
                    CitizenConfirmation::Commuting => 'Uczę się, pracuję lub przebywam w Szczecinie',
                },
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function identityFromData(array $data, ?string $ip, ?string $userAgent): VoterIdentityData
    {
        return new VoterIdentityData(
            pesel: $data['pesel'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            motherLastName: $data['mother_last_name'],
            phone: $data['phone'],
            ip: $ip,
            userAgent: $userAgent,
        );
    }
}
