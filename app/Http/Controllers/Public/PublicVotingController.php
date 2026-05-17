<?php

namespace App\Http\Controllers\Public;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Voting\Data\VoterIdentityData;
use App\Domain\Voting\Enums\CitizenConfirmation;
use App\Domain\Voting\Services\CastVoteService;
use App\Domain\Voting\Services\VotingTokenService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\CastPublicVoteRequest;
use App\Http\Requests\Public\IssueVotingTokenRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PublicVotingController extends Controller
{
    public function welcome(): View
    {
        return view('public.voting.welcome');
    }

    public function issueToken(IssueVotingTokenRequest $request, VotingTokenService $votingTokenService): RedirectResponse
    {
        Log::info('public_voting.issue_token.start', [
            'ip' => $request->ip(),
        ]);

        $data = $request->validated();

        try {
            $votingTokenService->issueSmsToken($this->identityFromData($data, $request->ip(), $request->userAgent()), $data['phone']);
        } catch (DomainException $exception) {
            Log::warning('public_voting.issue_token.rejected', [
                'reason' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['token' => $exception->getMessage()]);
        }

        Log::info('public_voting.issue_token.success', [
            'ip' => $request->ip(),
        ]);

        return back()->withInput()->with('status', 'Kod SMS został przygotowany.');
    }

    public function cast(CastPublicVoteRequest $request, CastVoteService $castVoteService, VotingTokenService $votingTokenService): RedirectResponse
    {
        Log::info('public_voting.cast.start', [
            'ip' => $request->ip(),
        ]);

        $data = $request->validated();
        $edition = BudgetEdition::query()->findOrFail($data['budget_edition_id']);
        $identity = $this->identityFromData($data, $request->ip(), $request->userAgent());
        $context = [
            'citizen_confirm' => isset($data['citizen_confirm']) ? CitizenConfirmation::from((int) $data['citizen_confirm']) : null,
            'confirm_missing_category' => (bool) ($data['confirm_missing_category'] ?? false),
            'parent_name' => $data['parent_name'] ?? null,
            'parent_confirm' => (bool) ($data['parent_confirm'] ?? false),
        ];

        try {
            $context['voting_token'] = $votingTokenService->activateSmsToken($data['phone'], $data['sms_token']);
            $voteCard = $castVoteService->cast(
                $edition,
                $identity,
                array_values(array_filter([$data['local_project_id'] ?? null])),
                array_values(array_filter([$data['citywide_project_id'] ?? null])),
                $context,
            );
        } catch (DomainException $exception) {
            Log::warning('public_voting.cast.rejected', [
                'reason' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['vote' => $exception->getMessage()]);
        }

        Log::info('public_voting.cast.success', [
            'vote_card_id' => $voteCard->id,
        ]);

        return redirect()->route('public.voting.welcome')->with('status', 'Głos został zapisany.');
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
