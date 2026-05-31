<?php

namespace App\Products\CivicBudget\Http\Controllers\Api\Mobile;

use App\Products\CivicBudget\Domain\BudgetEditions\Enums\BudgetEditionState;
use App\Products\CivicBudget\Domain\BudgetEditions\Models\BudgetEdition;
use App\Products\CivicBudget\Domain\BudgetEditions\Services\BudgetEditionStateResolver;
use App\Products\CivicBudget\Domain\Projects\Models\Category;
use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Products\CivicBudget\Domain\Projects\Models\ProjectArea;
use App\Http\Controllers\Controller;
use App\Products\CivicBudget\Http\Resources\Mobile\MobileCivicBudgetPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MobileCivicBudgetController extends Controller
{
    public function __construct(
        private readonly BudgetEditionStateResolver $stateResolver,
        private readonly MobileCivicBudgetPayload $payload,
    ) {}

    public function overview(): JsonResponse
    {
        Log::info('mobile_civic_budget.overview.start');

        $edition = $this->currentEdition();
        abort_unless($edition instanceof BudgetEdition, 404);

        $state = $this->stateResolver->resolve($edition);
        $projects = $this->publicProjectsQuery($edition)
            ->limit(3)
            ->get();

        $archivedEditions = BudgetEdition::query()
            ->whereKeyNot($edition->id)
            ->orderByDesc('propose_start')
            ->limit(5)
            ->get()
            ->map(fn (BudgetEdition $edition): array => $this->payload->edition($edition, BudgetEditionState::Inactive))
            ->values()
            ->all();

        Log::info('mobile_civic_budget.overview.success', [
            'budget_edition_id' => $edition->id,
            'featured_projects_count' => $projects->count(),
        ]);

        return response()->json([
            'activeEdition' => $this->payload->edition($edition, $state),
            'settings' => $this->payload->settings($edition, $state),
            'featuredProjects' => $projects
                ->map(fn (Project $project): array => $this->payload->project($project))
                ->values()
                ->all(),
            'archivedEditions' => $archivedEditions,
        ]);
    }

    public function activeEdition(): JsonResponse
    {
        Log::info('mobile_civic_budget.active_edition.start');

        $edition = $this->currentEdition();
        abort_unless($edition instanceof BudgetEdition, 404);
        $state = $this->stateResolver->resolve($edition);

        Log::info('mobile_civic_budget.active_edition.success', [
            'budget_edition_id' => $edition->id,
        ]);

        return response()->json($this->payload->edition($edition, $state));
    }

    private function currentEdition(): ?BudgetEdition
    {
        return BudgetEdition::query()
            ->orderByDesc('propose_start')
            ->first();
    }

    public function editions(): JsonResponse
    {
        Log::info('mobile_civic_budget.editions.start');

        $editions = BudgetEdition::query()
            ->orderByDesc('propose_start')
            ->get()
            ->map(fn (BudgetEdition $edition): array => $this->payload->edition($edition, $this->stateResolver->resolve($edition)))
            ->values()
            ->all();

        Log::info('mobile_civic_budget.editions.success', [
            'editions_count' => count($editions),
        ]);

        return response()->json(['items' => $editions]);
    }

    public function edition(BudgetEdition $edition): JsonResponse
    {
        Log::info('mobile_civic_budget.edition.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $state = $this->stateResolver->resolve($edition);

        Log::info('mobile_civic_budget.edition.success', [
            'budget_edition_id' => $edition->id,
        ]);

        return response()->json($this->payload->edition($edition, $state));
    }

    public function settings(BudgetEdition $edition): JsonResponse
    {
        Log::info('mobile_civic_budget.settings.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $state = $this->stateResolver->resolve($edition);

        Log::info('mobile_civic_budget.settings.success', [
            'budget_edition_id' => $edition->id,
        ]);

        return response()->json($this->payload->settings($edition, $state));
    }

    public function areas(BudgetEdition $edition): JsonResponse
    {
        Log::info('mobile_civic_budget.areas.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $areas = ProjectArea::query()
            ->orderBy('name')
            ->get()
            ->map(fn (ProjectArea $area): array => $this->payload->area($area))
            ->values()
            ->all();

        Log::info('mobile_civic_budget.areas.success', [
            'budget_edition_id' => $edition->id,
            'areas_count' => count($areas),
        ]);

        return response()->json(['items' => $areas]);
    }

    public function categories(BudgetEdition $edition): JsonResponse
    {
        Log::info('mobile_civic_budget.categories.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $categories = Category::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => $this->payload->category($category, $edition))
            ->values()
            ->all();

        Log::info('mobile_civic_budget.categories.success', [
            'budget_edition_id' => $edition->id,
            'categories_count' => count($categories),
        ]);

        return response()->json(['items' => $categories]);
    }

    public function projects(Request $request, BudgetEdition $edition): JsonResponse
    {
        Log::info('mobile_civic_budget.projects.start', [
            'budget_edition_id' => $edition->id,
            'has_query' => filled($request->query('q')),
        ]);

        $projects = $this->publicProjectsQuery($edition)
            ->when($request->integer('area_id') > 0, fn ($query) => $query->where('project_area_id', $request->integer('area_id')))
            ->when($request->integer('category_id') > 0, fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when(filled($request->query('q')), function ($query) use ($request): void {
                $query->where('title', 'like', '%'.trim((string) $request->query('q')).'%');
            })
            ->limit(100)
            ->get()
            ->map(fn (Project $project): array => $this->payload->project($project))
            ->values()
            ->all();

        Log::info('mobile_civic_budget.projects.success', [
            'budget_edition_id' => $edition->id,
            'projects_count' => count($projects),
        ]);

        return response()->json(['items' => $projects]);
    }

    public function project(Project $project): JsonResponse
    {
        Log::info('mobile_civic_budget.project.start', [
            'project_id' => $project->id,
        ]);

        abort_unless(! $project->is_hidden, 404);
        $project->load(['area', 'category', 'categories', 'budgetEdition', 'costItems', 'publicFiles']);

        Log::info('mobile_civic_budget.project.success', [
            'project_id' => $project->id,
        ]);

        return response()->json($this->payload->project($project));
    }

    public function publicPetitions(BudgetEdition $edition): JsonResponse
    {
        Log::info('mobile_civic_budget.public_petitions.start', [
            'budget_edition_id' => $edition->id,
        ]);

        $projects = Project::query()
            ->with(['area', 'category', 'categories', 'budgetEdition', 'costItems', 'publicFiles', 'corrections'])
            ->where('budget_edition_id', $edition->id)
            ->where('is_hidden', false)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->limit(100)
            ->get()
            ->map(fn (Project $project): array => $this->payload->residentProject($project))
            ->values()
            ->all();

        Log::info('mobile_civic_budget.public_petitions.success', [
            'budget_edition_id' => $edition->id,
            'projects_count' => count($projects),
        ]);

        return response()->json(['items' => $projects]);
    }

    public function publicPetition(Project $project): JsonResponse
    {
        Log::info('mobile_civic_budget.public_petition.start', [
            'project_id' => $project->id,
        ]);

        abort_unless(! $project->is_hidden && $project->submitted_at !== null, 404);
        $project->load(['area', 'category', 'categories', 'budgetEdition', 'costItems', 'publicFiles', 'corrections']);

        Log::info('mobile_civic_budget.public_petition.success', [
            'project_id' => $project->id,
        ]);

        return response()->json($this->payload->residentProject($project));
    }

    private function publicProjectsQuery(BudgetEdition $edition)
    {
        return Project::query()
            ->with(['area', 'category', 'categories', 'budgetEdition', 'costItems', 'publicFiles'])
            ->publiclyVisible()
            ->where('budget_edition_id', $edition->id)
            ->orderByRaw('number_drawn IS NULL')
            ->orderBy('number_drawn')
            ->orderByRaw('number IS NULL')
            ->orderBy('number')
            ->orderBy('title');
    }
}
