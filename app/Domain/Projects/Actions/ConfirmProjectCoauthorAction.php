<?php

namespace App\Domain\Projects\Actions;

use App\Domain\Projects\Models\ProjectCoauthor;
use DomainException;
use Illuminate\Support\Facades\Log;

class ConfirmProjectCoauthorAction
{
    public function execute(string $email, string $hash): ProjectCoauthor
    {
        Log::info('project.coauthor.confirm.start', [
            'email_hash' => hash('sha256', mb_strtolower(trim($email))),
        ]);

        $coauthor = ProjectCoauthor::query()
            ->where('email', $email)
            ->where('hash', $hash)
            ->first();

        if (! $coauthor instanceof ProjectCoauthor) {
            Log::warning('project.coauthor.confirm.rejected_not_found', [
                'email_hash' => hash('sha256', mb_strtolower(trim($email))),
            ]);

            throw new DomainException('Nie znaleziono współautora dla takiego e-maila oraz hashu');
        }

        if ($coauthor->confirm) {
            Log::info('project.coauthor.confirm.already_confirmed', [
                'project_id' => $coauthor->project_id,
                'coauthor_id' => $coauthor->id,
            ]);

            return $coauthor;
        }

        $coauthor->forceFill([
            'confirm' => true,
        ])->save();

        Log::info('project.coauthor.confirm.success', [
            'project_id' => $coauthor->project_id,
            'coauthor_id' => $coauthor->id,
        ]);

        return $coauthor->refresh();
    }
}
