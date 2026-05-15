<?php

namespace App\Http\Controllers\Public;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Files\Models\ProjectFile;
use App\Domain\Projects\Actions\SubmitProjectAction;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Services\PublicProjectCatalogQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StorePublicProjectRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PublicProjectController extends Controller
{
    public function index(Request $request, PublicProjectCatalogQuery $catalogQuery): View
    {
        return view('public.projects.index', [
            'projects' => $catalogQuery->paginate($request->only([
                'q',
                'budget_edition_id',
                'area_id',
                'category_id',
            ])),
            'budgetEditions' => BudgetEdition::query()->orderByDesc('propose_start')->get(),
            'areas' => ProjectArea::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function show(Project $project): View
    {
        abort_unless(Gate::allows('view', $project), 404);

        return view('public.projects.show', [
            'project' => $project->load(['area', 'budgetEdition', 'costItems', 'files']),
        ]);
    }

    public function create(): View
    {
        return view('public.projects.create', [
            'edition' => BudgetEdition::query()->latest('propose_start')->first(),
            'areas' => ProjectArea::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StorePublicProjectRequest $request, SubmitProjectAction $submitProject): RedirectResponse
    {
        Log::info('project.public_store.start', [
            'ip' => $request->ip(),
        ]);

        $data = $request->validated();

        $project = Project::query()->create([
            'budget_edition_id' => $data['budget_edition_id'],
            'project_area_id' => $data['project_area_id'],
            'title' => $data['title'],
            'localization' => $data['localization'],
            'description' => $data['description'],
            'goal' => $data['goal'],
            'argumentation' => $data['argumentation'],
            'availability' => $data['availability'],
            'recipients' => $data['recipients'],
            'free_of_charge' => $data['free_of_charge'],
            'status' => ProjectStatus::WorkingCopy,
            'is_support_list' => true,
        ]);

        $project->costItems()->create([
            'description' => $data['cost_description'],
            'amount' => $data['cost_amount'],
        ]);

        ProjectFile::query()->create([
            'project_id' => $project->id,
            'stored_name' => 'support-list-public-form',
            'original_name' => 'lista-poparcia',
            'description' => 'Potwierdzenie listy poparcia w formularzu publicznym',
            'type' => ProjectFileType::SupportList,
            'is_private' => true,
            'is_task_form_attachment' => true,
        ]);

        try {
            $submitProject->execute($project);
        } catch (DomainException $exception) {
            Log::warning('project.public_store.rejected', [
                'project_id' => $project->id,
                'reason' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['project' => $exception->getMessage()]);
        }

        Log::info('project.public_store.success', [
            'project_id' => $project->id,
        ]);

        return redirect()
            ->route('public.projects.index')
            ->with('status', 'Projekt został zgłoszony.');
    }
}
