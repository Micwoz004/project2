<?php

namespace App\Domain\Files\Actions;

use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Files\Models\ProjectFile;
use App\Domain\Files\Services\ProjectFileValidator;
use App\Domain\Projects\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class StoreProjectFileAction
{
    public function __construct(
        private readonly ProjectFileValidator $validator,
    ) {}

    public function execute(
        Project $project,
        ProjectFileType $type,
        UploadedFile $uploadedFile,
        ?User $actor = null,
        ?string $description = null,
        bool $isPrivate = false,
    ): ProjectFile {
        Log::info('project.file.store.start', [
            'project_id' => $project->id,
            'actor_id' => $actor?->id,
            'type' => $type->value,
            'is_private' => $isPrivate,
        ]);

        $this->validator->assertCanAttach($project, $type, $uploadedFile->getClientOriginalName(), $uploadedFile->getSize());

        $disk = $isPrivate ? 'local' : 'public';
        $directory = 'projects/'.$project->id.'/attachments';
        $storedName = $this->storedName($uploadedFile);
        $path = $uploadedFile->storeAs($directory, $storedName, $disk);

        if ($path === false) {
            Log::error('project.file.store.failed', [
                'project_id' => $project->id,
                'actor_id' => $actor?->id,
                'type' => $type->value,
                'disk' => $disk,
            ]);

            throw new RuntimeException('Project file could not be stored.');
        }

        $file = $project->files()->create([
            'stored_name' => $path,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'description' => $description,
            'type' => $type,
            'is_private' => $isPrivate,
            'created_by_id' => $actor?->id,
        ]);

        Log::info('project.file.store.success', [
            'project_id' => $project->id,
            'actor_id' => $actor?->id,
            'file_id' => $file->id,
            'disk' => $disk,
        ]);

        return $file;
    }

    private function storedName(UploadedFile $uploadedFile): string
    {
        $extension = Str::lower($uploadedFile->getClientOriginalExtension());

        return Str::uuid()->toString().'.'.$extension;
    }
}
