<?php

namespace App\Domain\Projects\Services;

use DomainException;
use Illuminate\Support\Facades\Log;

class ProjectCoauthorValidator
{
    private const MAX_COAUTHORS = 2;

    /**
     * @param  list<array<string, mixed>>  $coauthors
     */
    public function assertValid(array $coauthors, int $projectId): void
    {
        if (count($coauthors) > self::MAX_COAUTHORS) {
            Log::warning('project.coauthor.rejected_count_limit', [
                'project_id' => $projectId,
                'limit' => self::MAX_COAUTHORS,
            ]);

            throw new DomainException('Projekt może mieć maksymalnie dwóch współautorów.');
        }

        foreach ($coauthors as $index => $coauthor) {
            $this->assertCoauthorValid($coauthor, $projectId, $index + 1);
        }
    }

    /**
     * @param  array<string, mixed>  $coauthor
     */
    private function assertCoauthorValid(array $coauthor, int $projectId, int $number): void
    {
        if (empty($coauthor['first_name']) || empty($coauthor['last_name'])) {
            Log::warning('project.coauthor.rejected_missing_name', [
                'project_id' => $projectId,
                'number' => $number,
            ]);

            throw new DomainException('Współautor musi mieć imię i nazwisko.');
        }

        if (empty($coauthor['email']) && empty($coauthor['phone'])) {
            Log::warning('project.coauthor.rejected_missing_contact', [
                'project_id' => $projectId,
                'number' => $number,
            ]);

            throw new DomainException('Współautor musi mieć e-mail lub telefon.');
        }

        if (($coauthor['read_confirm'] ?? false) !== true) {
            Log::warning('project.coauthor.rejected_missing_read_confirm', [
                'project_id' => $projectId,
                'number' => $number,
            ]);

            throw new DomainException('Współautor musi potwierdzić zapoznanie się z informacją.');
        }

        if (($coauthor['email_agree'] ?? false) !== true && ($coauthor['phone_agree'] ?? false) !== true) {
            Log::warning('project.coauthor.rejected_missing_public_contact', [
                'project_id' => $projectId,
                'number' => $number,
            ]);

            throw new DomainException('Współautor musi wybrać co najmniej jedną formę kontaktu.');
        }
    }
}
