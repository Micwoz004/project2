<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Verification\Enums\VerificationAssignmentType;
use App\Domain\Verification\Models\VerificationVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use JsonException;

class RecordVerificationVersionAction
{
    /**
     * @param  array<string, mixed>  $snapshot
     *
     * @throws JsonException
     */
    public function execute(
        Model $verification,
        VerificationAssignmentType $type,
        User $actor,
        array $snapshot,
    ): VerificationVersion {
        Log::info('verification.version.record.start', [
            'verification_id' => $verification->getKey(),
            'type' => $type->value,
            'actor_id' => $actor->id,
        ]);

        $version = VerificationVersion::query()->create([
            'verification_legacy_id' => $verification->getKey(),
            'type' => $type->value,
            'user_id' => $actor->id,
            'raw_data' => json_encode($snapshot, JSON_THROW_ON_ERROR),
        ]);

        Log::info('verification.version.record.success', [
            'verification_id' => $verification->getKey(),
            'version_id' => $version->id,
            'type' => $type->value,
            'actor_id' => $actor->id,
        ]);

        return $version;
    }
}
