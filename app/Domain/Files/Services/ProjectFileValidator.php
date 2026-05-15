<?php

namespace App\Domain\Files\Services;

use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Projects\Models\Project;
use DomainException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProjectFileValidator
{
    public function assertCanAttach(Project $project, ProjectFileType $type, string $originalName, int $sizeBytes): void
    {
        $extension = Str::lower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (! in_array($extension, ProjectFileType::legacyAllowedExtensions(), true)) {
            Log::warning('project.file.rejected_extension', [
                'project_id' => $project->id,
                'type' => $type->value,
                'extension' => $extension,
            ]);

            throw new DomainException('Niedozwolony typ pliku załącznika.');
        }

        if ($sizeBytes > ProjectFileType::LEGACY_MAX_FILE_SIZE_BYTES) {
            Log::warning('project.file.rejected_size', [
                'project_id' => $project->id,
                'type' => $type->value,
            ]);

            throw new DomainException('Plik przekracza maksymalny rozmiar legacy.');
        }

        $currentFiles = $project->files()
            ->where('type', $type->value)
            ->count();

        if ($currentFiles >= $type->maxFiles()) {
            Log::warning('project.file.rejected_count_limit', [
                'project_id' => $project->id,
                'type' => $type->value,
                'limit' => $type->maxFiles(),
            ]);

            throw new DomainException('Przekroczono maksymalną liczbę załączników danego typu.');
        }
    }
}
