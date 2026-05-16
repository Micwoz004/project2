<?php

namespace App\Domain\Verification\Services;

use App\Domain\Projects\Models\Project;
use App\Domain\Verification\Enums\VerificationAssignmentType;
use App\Domain\Verification\Enums\VerificationCardStatus;
use App\Domain\Verification\Models\VerificationVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class VerificationOverviewService
{
    public function overviewText(Project $project): string
    {
        Log::info('verification.overview.start', [
            'project_id' => $project->id,
        ]);

        $project->loadMissing([
            'verificationAssignments.department',
            'formalVerifications',
            'initialMeritVerifications.department',
            'finalMeritVerifications.department',
            'consultationVerifications.department',
        ]);

        $lines = [
            'Przydziały:',
            ...$this->assignmentLines($project),
            '',
            'Karty:',
            ...$this->cardLines($project),
        ];

        Log::info('verification.overview.success', [
            'project_id' => $project->id,
            'assignments_count' => $project->verificationAssignments->count(),
        ]);

        return implode(PHP_EOL, $lines);
    }

    public function versionsText(Project $project): string
    {
        Log::info('verification.versions_overview.start', [
            'project_id' => $project->id,
        ]);

        $project->loadMissing([
            'formalVerifications',
            'initialMeritVerifications',
            'finalMeritVerifications',
            'consultationVerifications',
        ]);

        $versions = collect([
            ...$this->versionsFor($project->formalVerifications, VerificationAssignmentType::FormalVerification),
            ...$this->versionsFor($project->initialMeritVerifications, VerificationAssignmentType::MeritInitial),
            ...$this->versionsFor($project->finalMeritVerifications, VerificationAssignmentType::MeritFinish),
            ...$this->versionsFor($project->consultationVerifications, VerificationAssignmentType::Consultation),
        ])->sortBy('created_at')->values();

        Log::info('verification.versions_overview.success', [
            'project_id' => $project->id,
            'versions_count' => $versions->count(),
        ]);

        if ($versions->isEmpty()) {
            return 'Brak wersji kart weryfikacji.';
        }

        return $versions
            ->map(fn (VerificationVersion $version): string => implode(' | ', [
                $this->assignmentTypeLabel(VerificationAssignmentType::from($version->type)),
                'wersja #'.$version->id,
                'operator: '.($version->user?->name ?: '---'),
                'data: '.($version->created_at?->toDateTimeString() ?: '---'),
            ]))
            ->implode(PHP_EOL);
    }

    /**
     * @return list<string>
     */
    private function assignmentLines(Project $project): array
    {
        if ($project->verificationAssignments->isEmpty()) {
            return ['Brak przydziałów.'];
        }

        return $project->verificationAssignments
            ->sortBy([
                ['type', 'asc'],
                ['department_id', 'asc'],
            ])
            ->map(fn ($assignment): string => implode(' | ', [
                $this->assignmentTypeLabel($assignment->type),
                'jednostka: '.($assignment->department?->name ?: '---'),
                'termin: '.($assignment->deadline?->toDateTimeString() ?: '---'),
                'wysłano: '.($assignment->sent_at?->toDateTimeString() ?: 'nie'),
            ]))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function cardLines(Project $project): array
    {
        $lines = [];

        foreach ($project->formalVerifications as $verification) {
            $lines[] = implode(' | ', [
                'Formalna',
                $this->resultLabel($verification->result),
                'status projektu: '.$verification->status,
            ]);
        }

        foreach ($project->initialMeritVerifications as $verification) {
            $lines[] = $this->meritCardLine('Wstępna', $verification);
        }

        foreach ($project->finalMeritVerifications as $verification) {
            $lines[] = $this->meritCardLine('Końcowa', $verification);
        }

        foreach ($project->consultationVerifications as $verification) {
            $lines[] = $this->meritCardLine('Konsultacja', $verification);
        }

        return $lines === [] ? ['Brak kart weryfikacji.'] : $lines;
    }

    private function meritCardLine(string $label, mixed $verification): string
    {
        return implode(' | ', [
            $label,
            'jednostka: '.($verification->department?->name ?: '---'),
            $this->cardStatusLabel($verification->status),
            $this->resultLabel($verification->result),
            'wysłano: '.($verification->sent_at?->toDateTimeString() ?: 'nie'),
        ]);
    }

    /**
     * @return list<VerificationVersion>
     */
    private function versionsFor(Collection $verifications, VerificationAssignmentType $type): array
    {
        $ids = $verifications->pluck('id')->all();

        if ($ids === []) {
            return [];
        }

        return VerificationVersion::query()
            ->with('user')
            ->where('type', $type->value)
            ->whereIn('verification_legacy_id', $ids)
            ->orderBy('created_at')
            ->get()
            ->all();
    }

    private function assignmentTypeLabel(VerificationAssignmentType $type): string
    {
        return match ($type) {
            VerificationAssignmentType::MeritInitial => 'Weryfikacja wstępna',
            VerificationAssignmentType::MeritFinish => 'Weryfikacja końcowa',
            VerificationAssignmentType::Consultation => 'Konsultacja',
            VerificationAssignmentType::FormalVerification => 'Weryfikacja formalna',
        };
    }

    private function cardStatusLabel(VerificationCardStatus $status): string
    {
        return match ($status) {
            VerificationCardStatus::WorkingCopy => 'kopia robocza',
            VerificationCardStatus::Sent => 'wysłana',
        };
    }

    private function resultLabel(?bool $result): string
    {
        return match ($result) {
            true => 'wynik pozytywny',
            false => 'wynik negatywny',
            null => 'wynik nieustalony',
        };
    }
}
