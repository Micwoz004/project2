<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Verification\Enums\VerificationAssignmentType;
use App\Domain\Verification\Enums\VerificationCardStatus;
use App\Domain\Verification\Models\ConsultationVerification;
use App\Domain\Verification\Models\FinalMeritVerification;
use App\Domain\Verification\Models\InitialMeritVerification;
use App\Domain\Verification\Models\VerificationAssignment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReturnVerificationCardAction
{
    public function __construct(
        private readonly RecordVerificationVersionAction $recordVerificationVersion,
    ) {}

    public function execute(
        InitialMeritVerification|FinalMeritVerification|ConsultationVerification $verification,
        User $actor,
    ): InitialMeritVerification|FinalMeritVerification|ConsultationVerification {
        $type = $this->assignmentType($verification);

        Log::info('verification.card.return.start', [
            'verification_id' => $verification->id,
            'project_id' => $verification->project_id,
            'department_id' => $verification->department_id,
            'type' => $type->value,
            'actor_id' => $actor->id,
        ]);

        $assignment = $this->assignment($verification, $type);

        return DB::transaction(function () use ($verification, $actor, $type, $assignment): InitialMeritVerification|FinalMeritVerification|ConsultationVerification {
            $verification->forceFill([
                'modified_by_id' => $actor->id,
                'status' => VerificationCardStatus::WorkingCopy,
                'sent_at' => null,
            ])->save();

            $assignment->forceFill([
                'sent_at' => null,
                'is_returned' => true,
            ])->save();

            $this->recordVerificationVersion->execute(
                $verification,
                $type,
                $actor,
                [
                    'project_id' => $verification->project_id,
                    'department_id' => $verification->department_id,
                    'status' => VerificationCardStatus::WorkingCopy->value,
                    'result' => $verification->result,
                    'result_comments' => $verification->result_comments,
                    'answers' => $verification->answers ?? [],
                    'sent_at' => null,
                    'is_returned' => true,
                ],
            );

            Log::info('verification.card.return.success', [
                'verification_id' => $verification->id,
                'project_id' => $verification->project_id,
                'department_id' => $verification->department_id,
                'type' => $type->value,
                'actor_id' => $actor->id,
            ]);

            return $verification->refresh();
        });
    }

    private function assignmentType(
        InitialMeritVerification|FinalMeritVerification|ConsultationVerification $verification,
    ): VerificationAssignmentType {
        return match (true) {
            $verification instanceof InitialMeritVerification => VerificationAssignmentType::MeritInitial,
            $verification instanceof FinalMeritVerification => VerificationAssignmentType::MeritFinish,
            $verification instanceof ConsultationVerification => VerificationAssignmentType::Consultation,
        };
    }

    private function assignment(
        InitialMeritVerification|FinalMeritVerification|ConsultationVerification $verification,
        VerificationAssignmentType $type,
    ): VerificationAssignment {
        $assignment = VerificationAssignment::query()
            ->where('project_id', $verification->project_id)
            ->where('department_id', $verification->department_id)
            ->where('type', $type->value)
            ->first();

        if (! $assignment instanceof VerificationAssignment) {
            Log::warning('verification.card.return.rejected_missing_assignment', [
                'verification_id' => $verification->id,
                'project_id' => $verification->project_id,
                'department_id' => $verification->department_id,
                'type' => $type->value,
            ]);

            throw new DomainException('Nie znaleziono przydziału dla cofanej karty weryfikacji.');
        }

        return $assignment;
    }
}
