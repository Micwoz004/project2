<?php

namespace App\Domain\Voting\Services;

use App\Domain\Voting\Data\VoterIdentityData;
use App\Domain\Voting\Enums\VotingTokenType;
use App\Domain\Voting\Models\VotingToken;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VotingTokenService
{
    private const SMS_LIMIT_PER_PHONE = 5;

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

        return DB::transaction(function () use ($identity, $phone): VotingToken {
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
    }
}
