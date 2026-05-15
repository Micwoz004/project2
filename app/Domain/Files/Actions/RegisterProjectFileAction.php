<?php

namespace App\Domain\Files\Actions;

use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Files\Models\ProjectFile;
use App\Domain\Files\Services\ProjectFileValidator;
use App\Domain\Projects\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RegisterProjectFileAction
{
    public function __construct(
        private readonly ProjectFileValidator $validator,
    ) {}

    public function execute(
        Project $project,
        ProjectFileType $type,
        string $storedName,
        string $originalName,
        int $sizeBytes,
        ?User $actor = null,
        ?string $description = null,
        bool $isPrivate = false,
    ): ProjectFile {
        Log::info('project.file.register.start', [
            'project_id' => $project->id,
            'actor_id' => $actor?->id,
            'type' => $type->value,
        ]);

        $this->validator->assertCanAttach($project, $type, $originalName, $sizeBytes);

        $file = $project->files()->create([
            'stored_name' => $storedName,
            'original_name' => $originalName,
            'description' => $description,
            'type' => $type,
            'is_private' => $isPrivate,
            'created_by_id' => $actor?->id,
        ]);

        Log::info('project.file.register.success', [
            'project_id' => $project->id,
            'actor_id' => $actor?->id,
            'file_id' => $file->id,
            'type' => $type->value,
        ]);

        return $file;
    }
}
