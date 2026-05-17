<?php

namespace App\Domain\Voting\Services;

use App\Domain\Voting\Data\VoterIdentityData;
use App\Domain\Voting\Enums\VotingTokenType;
use App\Domain\Voting\Models\VotingToken;
use App\Domain\Voting\Services\Sms\SmsProvider;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VotingTokenService
{
    private const SMS_LIMIT_PER_PHONE = 5;

    public function __construct(
        private readonly SmsProvider $smsProvider,
    ) {}

    public function issueSmsToken(VoterIdentityData $identity, string $phone): VotingToken
    {
        Log::info('voting.token.sms.start', [
            'pesel_hash' => hash('sha256', $identity->pesel),
            'phone_hash' => hash('sha256', $phone),
        ]);

        $smsCount = VotingToken::query()
            ->where('type', VotingTokenType::Sms->value)
            ->where('phone', $phone)
            ->count();

        if ($smsCount >= self::SMS_LIMIT_PER_PHONE) {
            Log::warning('voting.token.sms.rejected_phone_limit', [
                'phone_hash' => hash('sha256', $phone),
                'count' => $smsCount,
            ]);

            throw new DomainException('Przekroczono limit kodów SMS dla numeru telefonu.');
        }

        $token = DB::transaction(function () use ($identity, $phone): VotingToken {
            VotingToken::query()
                ->where('pesel', $identity->pesel)
                ->where('type', VotingTokenType::Sms->value)
                ->update(['disabled' => true]);

            $token = VotingToken::query()->create([
                'token' => (string) random_int(100000, 999999),
                'pesel' => $identity->pesel,
                'first_name' => $identity->firstName,
                'second_name' => $identity->secondName,
                'mother_last_name' => $identity->motherLastName,
                'last_name' => $identity->lastName,
                'email' => $identity->email,
                'phone' => $phone,
                'disabled' => false,
                'type' => VotingTokenType::Sms,
                'ip' => $identity->ip,
                'user_agent' => $identity->userAgent,
            ]);

            Log::info('voting.token.sms.success', [
                'token_id' => $token->id,
                'pesel_hash' => hash('sha256', $identity->pesel),
            ]);

            return $token;
        });

        try {
            $this->smsProvider->send($phone, $this->smsTokenMessage($token));
        } catch (DomainException $exception) {
            Log::warning('voting.token.sms.send_failed', [
                'token_id' => $token->id,
                'reason' => $exception->getMessage(),
            ]);

            $this->disableToken($token);

            throw $exception;
        }

        return $token;
    }

    public function activateSmsToken(string $phone, string $token): VotingToken
    {
        Log::info('voting.token.sms_activate.start', [
            'phone_hash' => hash('sha256', $phone),
        ]);

        $votingToken = VotingToken::query()
            ->where('type', VotingTokenType::Sms->value)
            ->where('phone', $phone)
            ->where('token', $token)
            ->where('disabled', false)
            ->first();

        if ($votingToken === null) {
            Log::warning('voting.token.sms_activate.rejected', [
                'phone_hash' => hash('sha256', $phone),
            ]);

            throw new DomainException('Kod dostępu jest nieprawidłowy.');
        }

        Log::info('voting.token.sms_activate.success', [
            'token_id' => $votingToken->id,
            'phone_hash' => hash('sha256', $phone),
        ]);

        return $votingToken;
    }

    public function assertActiveTokenForIdentity(VotingToken $token, VoterIdentityData $identity): void
    {
        Log::info('voting.token.identity_check.start', [
            'token_id' => $token->id,
            'pesel_hash' => hash('sha256', $identity->pesel),
        ]);

        if ($token->disabled || $token->type !== VotingTokenType::Sms || $token->pesel !== $identity->pesel) {
            Log::warning('voting.token.identity_check.rejected', [
                'token_id' => $token->id,
                'pesel_hash' => hash('sha256', $identity->pesel),
            ]);

            throw new DomainException('Token głosowania nie jest aktywny dla podanego wyborcy.');
        }

        Log::info('voting.token.identity_check.success', [
            'token_id' => $token->id,
            'pesel_hash' => hash('sha256', $identity->pesel),
        ]);
    }

    public function disableToken(VotingToken $token): VotingToken
    {
        Log::info('voting.token.disable.start', [
            'token_id' => $token->id,
        ]);

        $token->forceFill([
            'disabled' => true,
        ])->save();

        Log::info('voting.token.disable.success', [
            'token_id' => $token->id,
        ]);

        return $token->refresh();
    }

    private function smsTokenMessage(VotingToken $token): string
    {
        return str_replace(
            '{activationSmsToken}',
            $token->token,
            (string) config('services.sms.voting_token_message'),
        );
    }
}
