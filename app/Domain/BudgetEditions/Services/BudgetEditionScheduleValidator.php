<?php

namespace App\Domain\BudgetEditions\Services;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BudgetEditionScheduleValidator
{
    public function assertValid(array $data, ?int $ignoreId = null): void
    {
        Log::info('budget_edition.schedule.validate.start', [
            'budget_edition_id' => $ignoreId,
            'propose_start' => $data['propose_start'],
        ]);

        $proposeStart = Carbon::parse($data['propose_start']);
        $proposeEnd = Carbon::parse($data['propose_end']);
        $preVotingVerificationEnd = Carbon::parse($data['pre_voting_verification_end']);
        $votingStart = Carbon::parse($data['voting_start']);
        $votingEnd = Carbon::parse($data['voting_end']);
        $postVotingVerificationEnd = Carbon::parse($data['post_voting_verification_end']);
        $resultAnnouncementEnd = Carbon::parse($data['result_announcement_end']);

        $this->assertNoLegacyOverlap($proposeStart, $ignoreId);
        $this->assertDateOrder(
            $proposeStart,
            $proposeEnd,
            'propose_start',
            'Data rozpoczęcia składania propozycji projektów zadań nie może być późniejsza od daty zakończenia składania propozycji zadań.'
        );
        $this->assertDateOrder(
            $proposeEnd,
            $preVotingVerificationEnd,
            'pre_voting_verification_end',
            'Data zakończenia weryfikacji propozycji projektów zadań musi być późniejsza od daty rozpoczęcia weryfikacji propozycji projektów zadań.'
        );
        $this->assertDateOrder(
            $votingStart,
            $votingEnd,
            'voting_end',
            'Data zakończenia głosowania na propozycje projektów zadań musi być późniejsza od daty rozpoczęcia głosowania na propozycje projektów zadań.'
        );
        $this->assertDateOrder(
            $votingEnd,
            $postVotingVerificationEnd,
            'post_voting_verification_end',
            'Data zakończenia weryfikowania wyników głosowania na propozycje projektów zadań musi być późniejsza od daty rozpoczęcia weryfikowania wyników głosowania na propozycje projektów zadań.'
        );
        $this->assertDateOrder(
            $postVotingVerificationEnd,
            $resultAnnouncementEnd,
            'result_announcement_end',
            'Data ogłoszenia wyników głosowania na propozycje projektów zadań musi być późniejsza od daty zakończenia weryfikowania wyników głosowania.'
        );

        Log::info('budget_edition.schedule.validate.success', [
            'budget_edition_id' => $ignoreId,
        ]);
    }

    private function assertNoLegacyOverlap(Carbon $proposeStart, ?int $ignoreId): void
    {
        $query = BudgetEdition::query()
            ->where('result_announcement_end', '>', $proposeStart);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if (! $query->exists()) {
            return;
        }

        Log::warning('budget_edition.schedule.rejected_overlap', [
            'budget_edition_id' => $ignoreId,
            'propose_start' => $proposeStart->toDateTimeString(),
        ]);

        throw new DomainException('Głosowania muszą być w odrębnych terminach.');
    }

    private function assertDateOrder(Carbon $start, Carbon $end, string $field, string $message): void
    {
        if ($start->lessThanOrEqualTo($end)) {
            return;
        }

        Log::warning('budget_edition.schedule.rejected_date_order', [
            'field' => $field,
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
        ]);

        throw new DomainException($message);
    }
}
