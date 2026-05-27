<?php

namespace App\Http\Controllers\Public;

use App\Domain\BudgetEditions\Enums\BudgetEditionState;
use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\BudgetEditions\Services\BudgetEditionStateResolver;
use App\Domain\Communications\Models\ProjectPublicComment;
use App\Domain\Communications\Services\ProjectPublicCommentVisibilityService;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Services\PublicProjectCatalogQuery;
use App\Domain\Projects\Services\PublicProjectMapQuery;
use App\Domain\Projects\Support\LegacyProjectFormText;
use App\Domain\Results\Services\ResultsCalculator;
use App\Domain\Results\Services\ResultsPublicationService;
use App\Domain\Settings\Models\CostGuideItem;
use App\Domain\Settings\Models\PublicAnnouncement;
use App\Domain\Settings\Models\PublicPage;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicSpaController extends Controller
{
    public function __invoke(
        Request $request,
        BudgetEditionStateResolver $stateResolver,
        PublicProjectCatalogQuery $catalogQuery,
        PublicProjectMapQuery $mapQuery,
        ProjectPublicCommentVisibilityService $commentVisibility,
        ResultsCalculator $resultsCalculator,
        ResultsPublicationService $publicationService,
    ): View {
        Log::info('public_spa.show.start', [
            'path' => $request->path(),
        ]);

        $edition = BudgetEdition::query()->latest('propose_start')->first();
        $state = $edition instanceof BudgetEdition
            ? $stateResolver->resolve($edition)
            : BudgetEditionState::Inactive;

        $mapPoints = $this->mapPointsPayload($request, $mapQuery);
        $results = $this->resultsPayload($edition, $resultsCalculator, $publicationService);
        $projects = $this->projectCollection(
            $request,
            $catalogQuery,
            $commentVisibility,
            collect($mapPoints)->pluck('project_id')->all(),
            $results['published'] ? $results['project_ids'] : [],
        );

        $announcements = PublicAnnouncement::query()
            ->published()
            ->latest('published_at')
            ->limit(12)
            ->get();

        if (str_starts_with($request->path(), 'ogloszenia/')) {
            $slug = Str::after($request->path(), 'ogloszenia/');
            abort_unless($announcements->contains('slug', $slug), 404);
        }

        $pages = PublicPage::query()
            ->published()
            ->orderBy('sort')
            ->orderBy('title')
            ->get();

        $spaState = [
            'app' => [
                'title' => 'Budżet Obywatelski Miasta',
                'currentPath' => $request->getPathInfo(),
                'csrfToken' => csrf_token(),
                'flash' => session('status'),
                'errors' => $request->session()->get('errors')?->getMessages() ?? [],
                'old' => $request->session()->getOldInput(),
                'authenticated' => $request->user() !== null,
                'userId' => $request->user()?->id,
            ],
            'links' => $this->links(),
            'edition' => $this->editionPayload($edition, $state),
            'stats' => $this->statsPayload($edition),
            'timeline' => $this->timelinePayload($edition, $state),
            'projects' => $this->projectsPayload($projects),
            'mapPoints' => $mapPoints,
            'results' => $results,
            'voting' => $request->path() === 'glosowanie' ? $this->votingPayload() : $this->emptyVotingPayload(),
            'areas' => $this->optionsPayload(ProjectArea::query()->orderBy('name')->get()),
            'categories' => $this->optionsPayload(Category::query()->orderBy('name')->get()),
            'announcements' => $this->announcementsPayload($announcements),
            'pages' => $this->pagesPayload($pages),
            'costGuideItems' => $this->costGuideItemsPayload(),
            'legacyText' => $request->path() === 'projekty/zglos'
                ? LegacyProjectFormText::publicSubmissionStatements()
                : [],
        ];

        Log::info('public_spa.show.success', [
            'path' => $request->path(),
            'budget_edition_id' => $edition?->id,
            'projects_count' => $projects->count(),
            'announcements_count' => $announcements->count(),
        ]);

        return view('public.spa', [
            'spaState' => $spaState,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function links(): array
    {
        return [
            'home' => route('public.home'),
            'projects' => route('public.projects.index'),
            'submit' => route('public.projects.create'),
            'map' => route('public.projects.map'),
            'voting' => route('public.voting.welcome'),
            'results' => route('public.results.index'),
            'announcements' => route('public.announcements.index'),
            'reports' => route('public.reports.index'),
            'projectStore' => route('public.projects.store'),
            'voteToken' => route('public.voting.token'),
            'voteCast' => route('public.voting.cast'),
            'admin' => url('/admin'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function editionPayload(?BudgetEdition $edition, BudgetEditionState $state): array
    {
        return [
            'id' => $edition?->id,
            'name' => $edition instanceof BudgetEdition ? 'Edycja '.$edition->propose_start?->format('Y') : 'Edycja lokalna',
            'state' => $state->value,
            'stateLabel' => $this->stateLabel($state),
            'stateDate' => $this->stateDate($edition, $state),
            'proposeStart' => $edition?->propose_start?->format('Y-m-d'),
            'proposeEnd' => $edition?->propose_end?->format('Y-m-d'),
            'votingStart' => $edition?->voting_start?->format('Y-m-d'),
            'votingEnd' => $edition?->voting_end?->format('Y-m-d'),
            'resultEnd' => $edition?->result_announcement_end?->format('Y-m-d'),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function statsPayload(?BudgetEdition $edition): array
    {
        if (! $edition instanceof BudgetEdition) {
            return [
                'projects' => 0,
                'picked' => 0,
                'announcements' => PublicAnnouncement::query()->published()->count(),
            ];
        }

        return [
            'projects' => Project::query()
                ->where('budget_edition_id', $edition->id)
                ->whereIn('status', [ProjectStatus::Submitted->value, ProjectStatus::Picked->value])
                ->count(),
            'picked' => Project::query()
                ->where('budget_edition_id', $edition->id)
                ->pickedForVoting()
                ->count(),
            'announcements' => PublicAnnouncement::query()->published()->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function timelinePayload(?BudgetEdition $edition, BudgetEditionState $state): array
    {
        $items = [
            [BudgetEditionState::Propose, 'Zgłaszanie', $edition?->propose_end],
            [BudgetEditionState::PreVotingVerification, 'Weryfikacja', $edition?->pre_voting_verification_end],
            [BudgetEditionState::PreVotingCorrection, 'Lista projektów', $edition?->voting_start],
            [BudgetEditionState::Voting, 'Głosowanie', $edition?->voting_end],
            [BudgetEditionState::ResultAnnouncement, 'Wyniki', $edition?->result_announcement_end],
        ];

        return collect($items)
            ->map(fn (array $item, int $index): array => [
                'index' => $index + 1,
                'state' => $item[0]->value,
                'label' => $item[1],
                'date' => $item[2]?->format('d.m.Y') ?? 'Data do ustalenia',
                'active' => $state === $item[0],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $publishedResultProjectIds
     * @return EloquentCollection<int, Project>
     */
    private function projectCollection(
        Request $request,
        PublicProjectCatalogQuery $catalogQuery,
        ProjectPublicCommentVisibilityService $commentVisibility,
        array $mapProjectIds,
        array $publishedResultProjectIds,
    ): EloquentCollection {
        $relations = ['area', 'category', 'categories', 'budgetEdition', 'costItems', 'publicFiles'];
        $path = $request->path();

        if (str_starts_with($path, 'projekt/')) {
            $projectId = (int) Str::after($path, 'projekt/');
            $project = Project::query()
                ->with([...$relations, 'publicComments.creator', 'publicComments.project'])
                ->publiclyVisible()
                ->whereKey($projectId)
                ->firstOrFail();

            $project->setRelation(
                'visiblePublicComments',
                $project->publicComments
                    ->filter(fn (ProjectPublicComment $comment): bool => $commentVisibility->canView($comment, $request->user()))
                    ->values(),
            );

            return new EloquentCollection([$project]);
        }

        if ($path === 'wyniki' && $publishedResultProjectIds === []) {
            return new EloquentCollection;
        }

        if ($path === 'projekty-mapa' && $mapProjectIds === []) {
            return new EloquentCollection;
        }

        $query = $catalogQuery->query($request->only([
            'q',
            'budget_edition_id',
            'area_id',
            'category_id',
        ]))->with($relations);

        if ($path === 'wyniki') {
            $query->whereIn('id', $publishedResultProjectIds);
        }

        if ($path === 'projekty-mapa') {
            $query->whereIn('id', $mapProjectIds);
        }

        return $query
            ->orderByRaw('number_drawn IS NULL')
            ->orderBy('number_drawn')
            ->orderByRaw('number IS NULL')
            ->orderBy('number')
            ->orderBy('title')
            ->limit(80)
            ->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapPointsPayload(Request $request, PublicProjectMapQuery $mapQuery): array
    {
        if ($request->path() !== 'projekty-mapa') {
            return [];
        }

        return $mapQuery->get($request->only([
            'q',
            'budget_edition_id',
            'area_id',
            'category_id',
        ]))
            ->map(fn (array $item): array => [
                'project_id' => $item['project']->id,
                'title' => $item['project']->title,
                'lat' => number_format($item['lat'], 7, '.', ''),
                'lng' => number_format($item['lng'], 7, '.', ''),
                'coords' => number_format($item['lat'], 7, '.', '').', '.number_format($item['lng'], 7, '.', ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{published: bool, message: string, totals: list<array<string, mixed>>, project_ids: list<int>}
     */
    private function resultsPayload(
        ?BudgetEdition $edition,
        ResultsCalculator $resultsCalculator,
        ResultsPublicationService $publicationService,
    ): array {
        if (! $edition instanceof BudgetEdition || ! $publicationService->canPublishPublicResults($edition)) {
            return [
                'published' => false,
                'message' => 'Wyniki nie zostały jeszcze opublikowane.',
                'totals' => [],
                'project_ids' => [],
            ];
        }

        $totals = $resultsCalculator->projectTotals($edition)
            ->map(fn ($total): array => [
                'project_id' => (int) $total->project_id,
                'points' => (int) $total->points,
            ])
            ->values();

        return [
            'published' => true,
            'message' => 'Wyniki zostały opublikowane.',
            'totals' => $totals->all(),
            'project_ids' => $totals->pluck('project_id')->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function votingPayload(): array
    {
        $edition = BudgetEdition::query()->latest('voting_start')->first();

        if (! $edition instanceof BudgetEdition) {
            return $this->emptyVotingPayload();
        }

        $projects = Project::query()
            ->with('area')
            ->pickedForVoting()
            ->where('budget_edition_id', $edition->id)
            ->whereHas('area')
            ->orderBy('number_drawn')
            ->get();

        $payload = fn (Project $project): array => [
            'id' => $project->id,
            'number' => $project->number_drawn ?? $project->number,
            'title' => $project->title,
            'area' => $project->area?->name,
        ];

        return [
            'edition_id' => $edition->id,
            'localProjects' => $projects
                ->filter(fn (Project $project): bool => (bool) $project->area?->is_local)
                ->map($payload)
                ->values()
                ->all(),
            'citywideProjects' => $projects
                ->filter(fn (Project $project): bool => ! (bool) $project->area?->is_local)
                ->map($payload)
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{edition_id: null, localProjects: list<array<mixed>>, citywideProjects: list<array<mixed>>}
     */
    private function emptyVotingPayload(): array
    {
        return [
            'edition_id' => null,
            'localProjects' => [],
            'citywideProjects' => [],
        ];
    }

    /**
     * @param  EloquentCollection<int, Project>  $projects
     * @return list<array<string, mixed>>
     */
    private function projectsPayload(EloquentCollection $projects): array
    {
        return $projects
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'url' => route('public.projects.show', $project),
                'title' => $project->title,
                'number' => $project->number_drawn ?? $project->number,
                'description' => Str::limit($project->short_description ?: $project->description, 260),
                'fullDescription' => $project->description,
                'goal' => $project->goal,
                'localization' => $project->localization,
                'areaId' => $project->project_area_id,
                'area' => $project->area?->name ?? 'Całe miasto',
                'categoryId' => $project->category_id,
                'category' => $project->category?->name ?? $project->categories->first()?->name ?? 'Projekt miejski',
                'categories' => $project->categories->pluck('name')->values()->all(),
                'status' => $this->publicStatusGroup($project->status),
                'statusLabel' => $project->publicStatusLabel(),
                'cost' => (float) ($project->cost_formatted ?? $project->costItems->sum('amount')),
                'costLabel' => number_format((float) ($project->cost_formatted ?? $project->costItems->sum('amount')), 0, ',', ' ').' zł',
                'lat' => $project->lat,
                'lng' => $project->lng,
                'files' => $project->publicFiles
                    ->map(fn ($file): array => [
                        'name' => $file->original_name,
                        'url' => $file->publicUrl(),
                    ])
                    ->values()
                    ->all(),
                'comments' => $project->getRelationValue('visiblePublicComments')
                    ?->map(fn (ProjectPublicComment $comment): array => [
                        'id' => $comment->id,
                        'content' => $comment->content,
                        'hidden' => $comment->hidden,
                        'state' => $comment->hidden ? 'Ukryty' : 'Widoczny',
                        'canManage' => auth()->id() === $comment->created_by_id,
                    ])
                    ->values()
                    ->all() ?? [],
            ])
            ->values()
            ->all();
    }

    private function publicStatusGroup(ProjectStatus $status): string
    {
        return match ($status) {
            ProjectStatus::Picked => 'live',
            ProjectStatus::PickedForRealization => 'ended',
            default => 'waiting',
        };
    }

    /**
     * @param  EloquentCollection<int, ProjectArea>|EloquentCollection<int, Category>  $items
     * @return list<array{id: int, name: string}>
     */
    private function optionsPayload(EloquentCollection $items): array
    {
        return $items
            ->map(fn (ProjectArea|Category $item): array => [
                'id' => $item->id,
                'name' => $item->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  EloquentCollection<int, PublicAnnouncement>  $announcements
     * @return list<array<string, mixed>>
     */
    private function announcementsPayload(EloquentCollection $announcements): array
    {
        return $announcements
            ->map(fn (PublicAnnouncement $announcement): array => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'slug' => $announcement->slug,
                'url' => route('public.announcements.show', $announcement->slug),
                'lead' => $announcement->lead ?: Str::limit(strip_tags($announcement->body), 160),
                'body' => $announcement->body,
                'date' => $announcement->published_at?->format('d.m.Y') ?? 'Komunikat',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  EloquentCollection<int, PublicPage>  $pages
     * @return list<array<string, mixed>>
     */
    private function pagesPayload(EloquentCollection $pages): array
    {
        return $pages
            ->map(fn (PublicPage $page): array => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'url' => route('public.info.show', $page->slug),
                'body' => $page->body,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function costGuideItemsPayload(): array
    {
        return CostGuideItem::query()
            ->published()
            ->orderBy('sort')
            ->orderBy('label')
            ->get()
            ->map(fn (CostGuideItem $item): array => [
                'id' => $item->id,
                'label' => $item->label,
                'priceRange' => $item->price_range,
            ])
            ->values()
            ->all();
    }

    private function stateLabel(BudgetEditionState $state): string
    {
        return match ($state) {
            BudgetEditionState::Propose => 'Trwa nabór projektów',
            BudgetEditionState::PreVotingVerification => 'Trwa weryfikacja projektów',
            BudgetEditionState::PreVotingCorrection => 'Przygotowanie listy do głosowania',
            BudgetEditionState::Voting => 'Trwa głosowanie',
            BudgetEditionState::PostVotingVerification => 'Weryfikacja wyników',
            BudgetEditionState::ResultAnnouncement => 'Publikacja wyników',
            default => 'Edycja poza aktywnym etapem',
        };
    }

    private function stateDate(?BudgetEdition $edition, BudgetEditionState $state): ?string
    {
        $date = match ($state) {
            BudgetEditionState::Propose => $edition?->propose_end,
            BudgetEditionState::PreVotingVerification => $edition?->pre_voting_verification_end,
            BudgetEditionState::Voting => $edition?->voting_end,
            BudgetEditionState::PostVotingVerification => $edition?->post_voting_verification_end,
            BudgetEditionState::ResultAnnouncement => $edition?->result_announcement_end,
            default => $edition?->propose_start,
        };

        return $date?->format('d.m.Y');
    }
}
