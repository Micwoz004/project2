<?php

namespace App\Domain\Projects\Actions;

use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Services\ProjectCoauthorValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncProjectCoauthorsAction
{
    public function __construct(
        private readonly ProjectCoauthorValidator $validator,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $coauthors
     */
    public function execute(Project $project, array $coauthors): void
    {
        Log::info('project.coauthor.sync.start', [
            'project_id' => $project->id,
            'count' => count($coauthors),
        ]);

        $this->validator->assertValid($coauthors, $project->id);

        DB::transaction(function () use ($project, $coauthors): void {
            $project->coauthors()->delete();

            foreach ($coauthors as $coauthor) {
                $project->coauthors()->create($coauthor);
            }
        });

        Log::info('project.coauthor.sync.success', [
            'project_id' => $project->id,
            'count' => count($coauthors),
        ]);
    }
}
