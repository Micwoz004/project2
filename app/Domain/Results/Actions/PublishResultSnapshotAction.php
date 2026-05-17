<?php

namespace App\Domain\Results\Actions;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Results\Models\ResultPublication;
use App\Domain\Results\Services\ResultsDashboardService;
use App\Domain\Results\Services\ResultsPublicationService;
use App\Domain\Users\Enums\SystemPermission;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublishResultSnapshotAction
{
    public function __construct(
        private readonly ResultsDashboardService $dashboardService,
        private readonly ResultsPublicationService $publicationService,
    ) {}

    public function execute(BudgetEdition $edition, User $operator): ResultPublication
    {
        Log::info('results.publication.snapshot.start', [
            'budget_edition_id' => $edition->id,
            'operator_id' => $operator->id,
        ]);

        if (! $this->canPublishSnapshot($operator)) {
            Log::warning('results.publication.snapshot.rejected_permission', [
                'budget_edition_id' => $edition->id,
                'operator_id' => $operator->id,
            ]);

            throw new DomainException('Brak uprawnień do utrwalenia publikacji wyników.');
        }

        if (! $this->publicationService->canPublishPublicResults($edition)) {
            Log::warning('results.publication.snapshot.rejected_state', [
                'budget_edition_id' => $edition->id,
                'operator_id' => $operator->id,
            ]);

            throw new DomainException('Wyniki można utrwalić dopiero w etapie publikacji.');
        }

        $summary = $this->dashboardService->summary($edition);

        $publication = DB::transaction(function () use ($edition, $operator, $summary): ResultPublication {
            $version = ((int) ResultPublication::query()
                ->where('budget_edition_id', $edition->id)
                ->max('version')) + 1;

            return ResultPublication::query()->create([
                'budget_edition_id' => $edition->id,
                'published_by_id' => $operator->id,
                'version' => $version,
                'total_points' => $summary['total_points'],
                'projects_count' => $summary['projects_count'],
                'project_totals' => $summary['project_totals'],
                'area_totals' => $summary['area_totals'],
                'category_totals' => $summary['category_totals'],
                'status_counts' => $summary['status_counts'],
                'tie_groups' => $summary['tie_groups'],
                'category_differences' => $summary['category_differences'],
                'published_at' => now(),
            ]);
        });

        Log::info('results.publication.snapshot.success', [
            'budget_edition_id' => $edition->id,
            'operator_id' => $operator->id,
            'result_publication_id' => $publication->id,
            'version' => $publication->version,
        ]);

        return $publication->refresh();
    }

    private function canPublishSnapshot(User $operator): bool
    {
        return $operator->can(SystemPermission::ReportsExport->value)
            || $operator->hasAnyRole(['admin', 'bdo']);
    }
}
