<?php

namespace App\Domain\Voting\Services;

use App\Domain\Communications\Models\MailLog;
use App\Domain\Voting\Enums\VotingTokenType;
use App\Domain\Voting\Models\SmsLog;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Models\VotingToken;
use App\Domain\Voting\Services\Sms\SmsProvider;
use DomainException;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class VoteSummaryNotificationService
{
    public function __construct(
        private readonly SmsProvider $smsProvider,
    ) {}

    public function sendAfterVote(VoteCard $voteCard, VotingToken $token): void
    {
        Log::info('voting.summary.send.start', [
            'vote_card_id' => $voteCard->id,
            'voter_id' => $voteCard->voter_id,
            'token_id' => $token->id,
            'token_type' => $token->type->value,
        ]);

        if ($token->type !== VotingTokenType::Sms) {
            Log::info('voting.summary.send.skipped_unsupported_token_type', [
                'vote_card_id' => $voteCard->id,
                'token_id' => $token->id,
                'token_type' => $token->type->value,
            ]);

            return;
        }

        $phone = trim((string) $token->phone);

        if ($phone === '') {
            Log::warning('voting.summary.sms.rejected_missing_phone', [
                'vote_card_id' => $voteCard->id,
                'token_id' => $token->id,
            ]);

            return;
        }

        try {
            $this->smsProvider->send($phone, $this->smsMessage($voteCard));
        } catch (DomainException $exception) {
            Log::warning('voting.summary.sms.send_failed', [
                'vote_card_id' => $voteCard->id,
                'voter_id' => $voteCard->voter_id,
                'reason' => $exception->getMessage(),
            ]);

            $this->recordSmsFailure($voteCard, $phone);

            return;
        }

        Log::info('voting.summary.sms.send.success', [
            'vote_card_id' => $voteCard->id,
            'voter_id' => $voteCard->voter_id,
        ]);
    }

    /**
     * @return list<array{area: string, number_drawn: int|null, title: string, points: int}>
     */
    public function summaryRows(VoteCard $voteCard): array
    {
        $voteCard->loadMissing('votes.project.area');

        return $voteCard->votes
            ->map(fn ($vote): array => [
                'area' => (string) ($vote->project->area?->symbol ?? $vote->project->area?->name ?? ''),
                'number_drawn' => $vote->project->number_drawn,
                'title' => $vote->project->title,
                'points' => (int) $vote->points,
            ])
            ->values()
            ->all();
    }

    private function smsMessage(VoteCard $voteCard): string
    {
        return str_replace(
            '{smsSummaryTable}',
            $this->smsSummaryTable($voteCard),
            (string) config('services.sms.voting_summary_message'),
        );
    }

    private function smsSummaryTable(VoteCard $voteCard): string
    {
        $rows = collect($this->summaryRows($voteCard))
            ->map(fn (array $vote): string => trim($vote['area'].' nr '.$vote['number_drawn'].' '.Str::limit($vote['title'], 15, '...').' '.$vote['points'].' głs'));

        if ($rows->isEmpty()) {
            return "\n";
        }

        return "\n".$rows->implode(', ').', ';
    }

    private function recordSmsFailure(VoteCard $voteCard, string $phone): void
    {
        $smsLog = SmsLog::query()->create([
            'phone' => $phone,
            'ip' => $voteCard->ip,
            'voter_id' => $voteCard->voter_id,
        ]);

        $subject = 'Błąd podczas przesyłania podsumowania SMS - '.$voteCard->voter_id;
        $body = 'Wystąpił błąd przesyłania podsumowania głosów w SMS - sprawdź logi.';
        $recipient = (string) config('mail.from.address');

        Mail::raw($body, function (Message $message) use ($recipient, $subject): void {
            $message
                ->to($recipient)
                ->subject($subject);
        });

        MailLog::query()->create([
            'email' => $recipient,
            'subject' => $subject,
            'content' => $body,
            'controller' => 'voting',
            'action' => 'sendVoteSummaryErrorEmail',
            'sent_at' => now(),
        ]);

        Log::info('voting.summary.sms.failure_recorded', [
            'vote_card_id' => $voteCard->id,
            'sms_log_id' => $smsLog->id,
        ]);
    }
}
