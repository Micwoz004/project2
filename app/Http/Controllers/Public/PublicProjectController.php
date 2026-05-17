<?php

namespace App\Http\Controllers\Public;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Communications\Models\ProjectPublicComment;
use App\Domain\Communications\Services\ProjectPublicCommentVisibilityService;
use App\Domain\Files\Actions\StoreProjectFileAction;
use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Projects\Actions\ApplyCorrectionAction;
use App\Domain\Projects\Actions\SubmitProjectAction;
use App\Domain\Projects\Actions\SyncProjectCoauthorsAction;
use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Models\ProjectCorrection;
use App\Domain\Projects\Services\ProjectCostLimitService;
use App\Domain\Projects\Services\PublicProjectCatalogQuery;
use App\Domain\Projects\Services\PublicProjectMapQuery;
use App\Domain\Projects\Support\LegacyProjectFormText;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StorePublicProjectRequest;
use App\Http\Requests\Public\UpdatePublicProjectCorrectionRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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

    public function map(Request $request, PublicProjectMapQuery $mapQuery): View
    {
        return view('public.projects.map', [
            'mapProjects' => $mapQuery->get($request->only([
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

    public function show(Request $request, Project $project, ProjectPublicCommentVisibilityService $commentVisibility): View
    {
        abort_unless(Gate::allows('view', $project), 404);
        $project->load(['area', 'budgetEdition', 'costItems', 'publicFiles']);

        $comments = $project->publicComments()
            ->with(['creator', 'project'])
            ->oldest()
            ->get()
            ->filter(fn (ProjectPublicComment $comment): bool => $commentVisibility->canView($comment, $request->user()))
            ->values();

        return view('public.projects.show', [
            'project' => $project,
            'publicComments' => $comments,
        ]);
    }

    public function editCorrection(Project $project): View
    {
        Gate::authorize('update', $project);

        $correction = $project->corrections()
            ->where('correction_done', false)
            ->where('correction_deadline', '>', now())
            ->latest()
            ->first();

        abort_unless($correction instanceof ProjectCorrection, 404);

        return view('public.projects.correction', [
            'project' => $project->load(['area', 'category', 'costItems']),
            'correction' => $correction,
            'areas' => ProjectArea::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function updateCorrection(
        UpdatePublicProjectCorrectionRequest $request,
        Project $project,
        ApplyCorrectionAction $applyCorrection,
        StoreProjectFileAction $storeProjectFile,
    ): RedirectResponse {
        Log::info('project.public_correction.start', [
            'project_id' => $project->id,
            'actor_id' => $request->actor()->id,
        ]);

        try {
            $data = $request->validated();
            $correction = $this->activeOpenCorrection($project);
            if ($this->storeCorrectionFilesFromAllowedInputs($request, $storeProjectFile, $project, $correction)) {
                $data['attachments_changed'] = true;
            }

            $updated = $applyCorrection->execute($project, $request->actor(), $data);
        } catch (DomainException $exception) {
            Log::warning('project.public_correction.rejected', [
                'project_id' => $project->id,
                'actor_id' => $request->actor()->id,
                'reason' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['project' => $exception->getMessage()]);
        }

        Log::info('project.public_correction.success', [
            'project_id' => $updated->id,
            'actor_id' => $request->actor()->id,
        ]);

        return redirect()
            ->route('public.projects.index')
            ->with('status', 'Korekta projektu została zapisana.');
    }

    public function create(): View
    {
        return view('public.projects.create', [
            'edition' => BudgetEdition::query()->latest('propose_start')->first(),
            'areas' => ProjectArea::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'legacyText' => LegacyProjectFormText::publicSubmissionStatements(),
        ]);
    }

    public function store(
        StorePublicProjectRequest $request,
        StoreProjectFileAction $storeProjectFile,
        SyncProjectCoauthorsAction $syncProjectCoauthors,
        SubmitProjectAction $submitProject,
        ProjectCostLimitService $costLimitService,
    ): RedirectResponse {
        Log::info('project.public_store.start', [
            'ip' => $request->ip(),
        ]);

        $data = $request->validated();
        $projectArea = $costLimitService->resolveSubmissionArea(
            ProjectArea::query()->findOrFail($data['project_area_id']),
            (int) $data['local'],
        );

        $project = Project::query()->create([
            'budget_edition_id' => $data['budget_edition_id'],
            'project_area_id' => $projectArea->id,
            'category_id' => $data['category_id'],
            'creator_id' => $request->user()?->id,
            'title' => $data['title'],
            'local' => $data['local'],
            'localization' => $data['localization'],
            'address' => $data['address'] ?? null,
            'plot' => $data['plot'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'map_lng_lat' => $data['map_lng_lat'] ?? null,
            'map_data' => $data['map_data'] ?? null,
            'description' => $data['description'],
            'goal' => $data['goal'],
            'argumentation' => $data['argumentation'],
            'availability' => $data['availability'],
            'recipients' => $data['recipients'],
            'free_of_charge' => $data['free_of_charge'],
            'short_description' => $data['short_description'] ?? null,
            'additional_cost' => $data['additional_cost'] ?? null,
            'contact_with' => $data['contact_with'],
            'attachments_anonymized' => true,
            'consent_to_change' => (bool) ($data['consent_to_change'] ?? false),
            'show_task_coauthors' => (bool) ($data['show_task_coauthors'] ?? true),
            'authors' => $request->authorSnapshot(),
            'status' => ProjectStatus::WorkingCopy,
            'is_support_list' => true,
            'cost' => collect($request->costItems())
                ->map(fn (array $costItem): string => $costItem['description'].': '.$costItem['amount'])
                ->implode(PHP_EOL),
            'cost_formatted' => collect($request->costItems())->sum('amount'),
        ]);
        $project->categories()->sync([$data['category_id']]);

        foreach ($request->costItems() as $costItem) {
            $project->costItems()->create($costItem);
        }

        try {
            $supportListFile = $request->file('support_list_file');

            if (! $supportListFile instanceof UploadedFile) {
                Log::warning('project.public_store.rejected_missing_support_file', [
                    'project_id' => $project->id,
                ]);

                return back()->withInput()->withErrors(['support_list_file' => 'Brak pliku listy poparcia.']);
            }

            $file = $storeProjectFile->execute(
                $project,
                ProjectFileType::SupportList,
                $supportListFile,
                null,
                'Lista poparcia z formularza publicznego',
                true,
            );
            $file->forceFill([
                'is_task_form_attachment' => true,
            ])->save();

            $this->storeProjectFilesFromInput($request, $storeProjectFile, $project, 'owner_agreement_files', ProjectFileType::OwnerAgreement, true);
            $this->storeProjectFilesFromInput($request, $storeProjectFile, $project, 'map_files', ProjectFileType::Map);
            $this->storeProjectFilesFromInput($request, $storeProjectFile, $project, 'parent_agreement_files', ProjectFileType::ParentAgreement, true);
            $this->storeProjectFilesFromInput($request, $storeProjectFile, $project, 'attachment_files', ProjectFileType::Other);

            $syncProjectCoauthors->execute($project, $request->coauthors());

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

    private function storeProjectFilesFromInput(
        Request $request,
        StoreProjectFileAction $storeProjectFile,
        Project $project,
        string $inputName,
        ProjectFileType $type,
        bool $isPrivate = false,
    ): int {
        $storedCount = 0;

        foreach ($this->uploadedFiles($request, $inputName) as $uploadedFile) {
            $file = $storeProjectFile->execute(
                $project,
                $type,
                $uploadedFile,
                null,
                $type->label().' z formularza publicznego',
                $isPrivate,
            );
            $file->forceFill([
                'is_task_form_attachment' => true,
            ])->save();

            $storedCount++;
        }

        return $storedCount;
    }

    /**
     * @return list<UploadedFile>
     */
    private function uploadedFiles(Request $request, string $inputName): array
    {
        $files = $request->file($inputName, []);

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter(
            $files,
            static fn (mixed $file): bool => $file instanceof UploadedFile,
        ));
    }

    private function activeOpenCorrection(Project $project): ?ProjectCorrection
    {
        $correction = $project->corrections()
            ->where('correction_done', false)
            ->where('correction_deadline', '>', now())
            ->latest()
            ->first();

        return $correction instanceof ProjectCorrection ? $correction : null;
    }

    private function storeCorrectionFilesFromAllowedInputs(
        UpdatePublicProjectCorrectionRequest $request,
        StoreProjectFileAction $storeProjectFile,
        Project $project,
        ?ProjectCorrection $correction,
    ): bool {
        if (! $correction instanceof ProjectCorrection) {
            return false;
        }

        $storedCount = 0;
        $storedCount += $this->storeCorrectionFilesIfAllowed($request, $storeProjectFile, $project, $correction, ProjectCorrectionField::SupportAttachment, 'support_list_files', ProjectFileType::SupportList, true);
        $storedCount += $this->storeCorrectionFilesIfAllowed($request, $storeProjectFile, $project, $correction, ProjectCorrectionField::AgreementAttachment, 'owner_agreement_files', ProjectFileType::OwnerAgreement, true);
        $storedCount += $this->storeCorrectionFilesIfAllowed($request, $storeProjectFile, $project, $correction, ProjectCorrectionField::MapAttachment, 'map_files', ProjectFileType::Map);
        $storedCount += $this->storeCorrectionFilesIfAllowed($request, $storeProjectFile, $project, $correction, ProjectCorrectionField::ParentAgreementAttachment, 'parent_agreement_files', ProjectFileType::ParentAgreement, true);
        $storedCount += $this->storeCorrectionFilesIfAllowed($request, $storeProjectFile, $project, $correction, ProjectCorrectionField::Attachments, 'attachment_files', ProjectFileType::Other);

        return $storedCount > 0;
    }

    private function storeCorrectionFilesIfAllowed(
        UpdatePublicProjectCorrectionRequest $request,
        StoreProjectFileAction $storeProjectFile,
        Project $project,
        ProjectCorrection $correction,
        ProjectCorrectionField $field,
        string $inputName,
        ProjectFileType $type,
        bool $isPrivate = false,
    ): int {
        if (! in_array($field->value, $correction->allowed_fields, true)) {
            return 0;
        }

        return $this->storeProjectFilesFromInput($request, $storeProjectFile, $project, $inputName, $type, $isPrivate);
    }
}
