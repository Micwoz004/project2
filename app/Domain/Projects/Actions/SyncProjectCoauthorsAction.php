<?php

namespace App\Domain\Projects\Actions;

use App\Domain\Communications\Actions\SendProjectCoauthorConfirmationAction;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectCoauthor;
use App\Domain\Projects\Services\ProjectCoauthorValidator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncProjectCoauthorsAction
{
    public function __construct(
        private readonly ProjectCoauthorValidator $validator,
        private readonly SendProjectCoauthorConfirmationAction $sendProjectCoauthorConfirmation,
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

        $createdCoauthors = DB::transaction(function () use ($project, $coauthors): Collection {
            $project->coauthors()->delete();

            return collect($coauthors)
                ->map(fn (array $coauthor): ProjectCoauthor => $project->coauthors()->create($coauthor));
        });

        $project->loadMissing('creator');
        $createdCoauthors->each(function (ProjectCoauthor $coauthor) use ($project): void {
            $this->sendProjectCoauthorConfirmation->execute($coauthor, $project->creator);
        });

        Log::info('project.coauthor.sync.success', [
            'project_id' => $project->id,
            'count' => count($coauthors),
        ]);
    }
}
