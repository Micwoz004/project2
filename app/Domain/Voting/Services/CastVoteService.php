<?php

namespace App\Domain\Voting\Services;

use App\Domain\BudgetEditions\Enums\BudgetEditionState;
use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\BudgetEditions\Services\BudgetEditionStateResolver;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Voting\Data\VoterIdentityData;
use App\Domain\Voting\Enums\CitizenConfirmation;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Models\Voter;
use App\Domain\Voting\Models\VoterRegistryHash;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CastVoteService
{
    private const MAX_LOCAL_POINTS = 1;

    private const MAX_CITYWIDE_POINTS = 1;

    public function __construct(
        private readonly BudgetEditionStateResolver $stateResolver,
        private readonly PeselService $peselService,
        private readonly VoterHashService $voterHashService,
    ) {}

    public function cast(
        BudgetEdition $edition,
        VoterIdentityData $identity,
        array $localProjectIds,
        array $citywideProjectIds,
        array $context = [],
    ): VoteCard {
        Log::info('voting.cast.start', [
            'budget_edition_id' => $edition->id,
            'pesel_hash' => $this->peselLogHash($identity),
            'local_count' => count($localProjectIds),
            'citywide_count' => count($citywideProjectIds),
        ]);

        $this->assertCanCast($edition, $identity, $localProjectIds, $citywideProjectIds, $context);

        return DB::transaction(function () use (
            $edition,
            $identity,
            $localProjectIds,
            $citywideProjectIds,
            $context,
        ): VoteCard {
            $birthDate = $identity->noPeselNumber ? null : $this->peselService->birthDate($identity->pesel);
            $voter = Voter::query()->create([
                'pesel' => $identity->noPeselNumber ? null : $identity->pesel,
                'first_name' => $identity->firstName,
                'second_name' => $identity->secondName,
                'mother_last_name' => $identity->motherLastName,
                'last_name' => $identity->lastName,
                'father_name' => $identity->fatherName,
                'email' => $identity->email,
                'phone' => $identity->phone,
                'ip' => $identity->ip,
                'user_agent' => $identity->userAgent,
                'birth_date' => $birthDate,
                'age' => $identity->noPeselNumber ? null : $this->peselService->age($identity->pesel),
                'sex' => $identity->noPeselNumber ? null : $this->peselService->sex($identity->pesel),
            ]);

            $voteCard = VoteCard::query()->create([
                'budget_edition_id' => $edition->id,
                'voter_id' => $voter->id,
                'statement' => true,
                'terms_accepted' => true,
                'city_statement' => (bool) ($context['city_statement'] ?? false),
                'no_pesel_number' => $identity->noPeselNumber,
                'digital' => true,
                'status' => $identity->noPeselNumber ? VoteCardStatus::Verifying : VoteCardStatus::Accepted,
                'citizen_confirm' => $context['citizen_confirm'] ?? null,
                'parent_name' => $context['parent_name'] ?? null,
                'parent_confirm' => (bool) ($context['parent_confirm'] ?? false),
                'ip' => $identity->ip,
            ]);

            foreach (array_merge($localProjectIds, $citywideProjectIds) as $projectId) {
                $voteCard->votes()->create([
                    'voter_id' => $voter->id,
                    'project_id' => $projectId,
                    'points' => 1,
                ]);
            }

            Log::info('voting.cast.success', [
                'vote_card_id' => $voteCard->id,
                'voter_id' => $voter->id,
                'status' => $voteCard->status->value,
            ]);

            return $voteCard->refresh();
        });
    }

    private function assertCanCast(
        BudgetEdition $edition,
        VoterIdentityData $identity,
        array $localProjectIds,
        array $citywideProjectIds,
        array $context,
    ): void {
        if ($this->stateResolver->resolve($edition) !== BudgetEditionState::Voting) {
            Log::warning('voting.cast.rejected_inactive_window', [
                'budget_edition_id' => $edition->id,
            ]);

            throw new DomainException('Głosowanie nie jest aktywne.');
        }

        if ($localProjectIds === [] && $citywideProjectIds === []) {
            Log::warning('voting.cast.rejected_empty_vote', [
                'budget_edition_id' => $edition->id,
            ]);

            throw new DomainException('Musisz oddać przynajmniej jeden głos.');
        }

        if (($localProjectIds === []) !== ($citywideProjectIds === [])
            && ($context['confirm_missing_category'] ?? false) !== true) {
            Log::warning('voting.cast.rejected_missing_category_confirmation', [
                'budget_edition_id' => $edition->id,
                'local_count' => count($localProjectIds),
                'citywide_count' => count($citywideProjectIds),
            ]);

            throw new DomainException('Brak głosu w jednej kategorii wymaga potwierdzenia.');
        }

        if (! $identity->noPeselNumber && ! $this->peselService->isValid($identity->pesel)) {
            Log::warning('voting.cast.rejected_invalid_pesel', [
                'pesel_hash' => $this->peselLogHash($identity),
            ]);

            throw new DomainException('Podany PESEL jest nieprawidłowy.');
        }

        if (! $identity->noPeselNumber) {
            $alreadyVoted = VoteCard::query()
                ->where('budget_edition_id', $edition->id)
                ->whereHas('voter', fn ($query) => $query->where('pesel', $identity->pesel))
                ->exists();

            if ($alreadyVoted) {
                Log::warning('voting.cast.rejected_duplicate_pesel', [
                    'budget_edition_id' => $edition->id,
                    'pesel_hash' => $this->peselLogHash($identity),
                ]);

                throw new DomainException('Podany PESEL brał już udział w głosowaniu.');
            }

            $this->assertVoterRegistryOrConfirmation($identity, $context);
            $this->assertParentConsent($identity, $context);
        }

        if (count($localProjectIds) > self::MAX_LOCAL_POINTS
            || count($citywideProjectIds) > self::MAX_CITYWIDE_POINTS) {
            Log::warning('voting.cast.rejected_points_limit', [
                'budget_edition_id' => $edition->id,
                'local_count' => count($localProjectIds),
                'citywide_count' => count($citywideProjectIds),
            ]);

            throw new DomainException('Przekroczono limit głosów.');
        }

        $this->assertProjectsArePicked($edition, $localProjectIds, true);
        $this->assertProjectsArePicked($edition, $citywideProjectIds, false);
    }

    private function assertProjectsArePicked(BudgetEdition $edition, array $projectIds, bool $local): void
    {
        if ($projectIds === []) {
            return;
        }

        $projects = Project::query()
            ->with('area')
            ->where('budget_edition_id', $edition->id)
            ->whereIn('id', $projectIds)
            ->where('status', ProjectStatus::Picked->value)
            ->get();

        if ($projects->count() !== count(array_unique($projectIds))) {
            Log::warning('voting.cast.rejected_project_not_picked', [
                'budget_edition_id' => $edition->id,
                'project_ids' => $projectIds,
            ]);

            throw new DomainException('Wybrany projekt nie znajduje się na liście do głosowania.');
        }

        $mismatched = $projects->first(fn (Project $project): bool => $project->area->is_local !== $local);

        if ($mismatched instanceof Project) {
            Log::warning('voting.cast.rejected_area_type_mismatch', [
                'budget_edition_id' => $edition->id,
                'project_id' => $mismatched->id,
            ]);

            throw new DomainException('Wybrany projekt należy do innej kategorii głosu.');
        }
    }

    private function assertVoterRegistryOrConfirmation(VoterIdentityData $identity, array $context): void
    {
        if ($this->isInVoterRegistry($identity)) {
            return;
        }

        if (($context['citizen_confirm'] ?? null) instanceof CitizenConfirmation) {
            return;
        }

        Log::warning('voting.cast.rejected_missing_registry_or_confirmation', [
            'pesel_hash' => $this->peselLogHash($identity),
        ]);

        throw new DomainException('Wyborca musi być potwierdzony w rejestrze albo złożyć oświadczenie.');
    }

    private function isInVoterRegistry(VoterIdentityData $identity): bool
    {
        $hashes = [
            $this->voterHashService->legacyLookupHash(
                $identity->pesel,
                $identity->firstName,
                $identity->lastName,
                $identity->motherLastName,
            ),
            $this->voterHashService->legacyLookupHash(
                $identity->pesel,
                $identity->firstName,
                $identity->lastName,
                'BRAK DANYCH',
            ),
            $this->voterHashService->legacyLookupHash(
                $identity->pesel,
                $identity->firstName,
                $identity->lastName,
                '',
            ),
        ];

        return VoterRegistryHash::query()
            ->whereIn('hash', array_unique($hashes))
            ->exists();
    }

    private function assertParentConsent(VoterIdentityData $identity, array $context): void
    {
        if (! $this->peselService->requiresParentConsent($identity->pesel)) {
            return;
        }

        if (($context['parent_confirm'] ?? false) === true && trim((string) ($context['parent_name'] ?? '')) !== '') {
            return;
        }

        Log::warning('voting.cast.rejected_missing_parent_consent', [
            'pesel_hash' => $this->peselLogHash($identity),
        ]);

        throw new DomainException('Wyborca niepełnoletni wymaga zgody rodzica lub opiekuna.');
    }

    private function peselLogHash(VoterIdentityData $identity): ?string
    {
        return $identity->noPeselNumber ? null : hash('sha256', $identity->pesel);
    }
}
