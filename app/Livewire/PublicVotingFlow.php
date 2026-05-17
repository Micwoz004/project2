<?php

namespace App\Livewire;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Models\Project;
use App\Domain\Voting\Data\VoterIdentityData;
use App\Domain\Voting\Enums\CitizenConfirmation;
use App\Domain\Voting\Services\CastVoteService;
use App\Domain\Voting\Services\VotingTokenService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PublicVotingFlow extends Component
{
    public ?int $budgetEditionId = null;

    public string $pesel = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $motherLastName = '';

    public string $phone = '';

    public string $smsToken = '';

    public ?int $localProjectId = null;

    public ?int $citywideProjectId = null;

    public ?int $citizenConfirm = null;

    public bool $confirmMissingCategory = false;

    public string $parentName = '';

    public bool $parentConfirm = false;

    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->budgetEditionId = BudgetEdition::query()
            ->latest('voting_start')
            ->value('id');
    }

    public function issueToken(VotingTokenService $votingTokenService): void
    {
        Log::info('public_voting.livewire.issue_token.start');

        $data = $this->validateTokenData();

        try {
            $votingTokenService->issueSmsToken($this->identityFromData($data), $data['phone']);
        } catch (DomainException $exception) {
            Log::warning('public_voting.livewire.issue_token.rejected', [
                'reason' => $exception->getMessage(),
            ]);

            $this->addError('token', $exception->getMessage());

            return;
        }

        $this->statusMessage = 'Kod SMS został przygotowany.';

        Log::info('public_voting.livewire.issue_token.success');
    }

    public function cast(CastVoteService $castVoteService, VotingTokenService $votingTokenService): void
    {
        Log::info('public_voting.livewire.cast.start', [
            'budget_edition_id' => $this->budgetEditionId,
        ]);

        $data = $this->validateVoteData();
        $edition = BudgetEdition::query()->findOrFail($data['budget_edition_id']);
        $identity = $this->identityFromData($data);

        try {
            $token = $votingTokenService->activateSmsToken($data['phone'], $data['sms_token']);
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
                    'voting_token' => $token,
                ],
            );
        } catch (DomainException $exception) {
            Log::warning('public_voting.livewire.cast.rejected', [
                'reason' => $exception->getMessage(),
            ]);

            $this->addError('vote', $exception->getMessage());

            return;
        }

        $this->smsToken = '';
        $this->statusMessage = 'Głos został zapisany.';

        Log::info('public_voting.livewire.cast.success', [
            'vote_card_id' => $voteCard->id,
        ]);
    }

    public function render(): View
    {
        $edition = $this->budgetEditionId
            ? BudgetEdition::query()->find($this->budgetEditionId)
            : null;

        return view('livewire.public-voting-flow', [
            'edition' => $edition,
            'localProjects' => $this->projectsForVoting(true),
            'citywideProjects' => $this->projectsForVoting(false),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTokenData(): array
    {
        return Validator::make($this->payload(), [
            'pesel' => ['required', 'string', 'size:11'],
            'first_name' => ['required', 'string', 'max:127'],
            'last_name' => ['required', 'string', 'max:127'],
            'mother_last_name' => ['required', 'string', 'max:127'],
            'phone' => ['required', 'string', 'max:30'],
        ])->validate();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVoteData(): array
    {
        return Validator::make($this->payload(), [
            'budget_edition_id' => ['required', 'exists:budget_editions,id'],
            'pesel' => ['required', 'string', 'size:11'],
            'first_name' => ['required', 'string', 'max:127'],
            'last_name' => ['required', 'string', 'max:127'],
            'mother_last_name' => ['required', 'string', 'max:127'],
            'phone' => ['required', 'string', 'max:30'],
            'sms_token' => ['required', 'string', 'size:6'],
            'local_project_id' => ['nullable', 'exists:projects,id'],
            'citywide_project_id' => ['nullable', 'exists:projects,id'],
            'citizen_confirm' => ['nullable', Rule::enum(CitizenConfirmation::class)],
            'confirm_missing_category' => ['boolean'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_confirm' => ['boolean'],
        ])->validate();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'budget_edition_id' => $this->budgetEditionId,
            'pesel' => $this->pesel,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'mother_last_name' => $this->motherLastName,
            'phone' => $this->phone,
            'sms_token' => $this->smsToken,
            'local_project_id' => $this->localProjectId,
            'citywide_project_id' => $this->citywideProjectId,
            'citizen_confirm' => $this->citizenConfirm,
            'confirm_missing_category' => $this->confirmMissingCategory,
            'parent_name' => $this->parentName,
            'parent_confirm' => $this->parentConfirm,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function identityFromData(array $data): VoterIdentityData
    {
        return new VoterIdentityData(
            pesel: $data['pesel'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            motherLastName: $data['mother_last_name'],
            phone: $data['phone'],
            ip: request()->ip(),
            userAgent: request()->userAgent(),
        );
    }

    private function projectsForVoting(bool $local)
    {
        return Project::query()
            ->with('area')
            ->pickedForVoting()
            ->whereHas('area', fn ($query) => $query->where('is_local', $local))
            ->orderBy('number_drawn')
            ->get();
    }
}
