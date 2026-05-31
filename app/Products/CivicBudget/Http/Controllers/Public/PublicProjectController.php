<?php

namespace App\Products\CivicBudget\Http\Controllers\Public;

use App\Products\CivicBudget\Domain\BudgetEditions\Models\BudgetEdition;
use App\Products\CivicBudget\Domain\Communications\Models\ProjectPublicComment;
use App\Products\CivicBudget\Domain\Communications\Services\ProjectPublicCommentVisibilityService;
use App\Products\CivicBudget\Domain\Files\Actions\StoreProjectFileAction;
use App\Products\CivicBudget\Domain\Files\Enums\ProjectFileType;
use App\Products\CivicBudget\Domain\Projects\Actions\ApplyCorrectionAction;
use App\Products\CivicBudget\Domain\Projects\Actions\SubmitProjectAction;
use App\Products\CivicBudget\Domain\Projects\Actions\SyncProjectCoauthorsAction;
use App\Products\CivicBudget\Domain\Projects\Enums\ProjectCorrectionField;
use App\Products\CivicBudget\Domain\Projects\Enums\ProjectStatus;
use App\Products\CivicBudget\Domain\Projects\Models\Category;
use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Products\CivicBudget\Domain\Projects\Models\ProjectArea;
use App\Products\CivicBudget\Domain\Projects\Models\ProjectCorrection;
use App\Products\CivicBudget\Domain\Projects\Services\ProjectCostLimitService;
use App\Products\CivicBudget\Domain\Projects\Services\PublicProjectCatalogQuery;
use App\Products\CivicBudget\Domain\Projects\Services\PublicProjectMapQuery;
use App\Products\CivicBudget\Domain\Projects\Support\LegacyProjectFormText;
use App\Http\Controllers\Controller;
use App\Products\CivicBudget\Http\Requests\Public\StorePublicProjectRequest;
use App\Products\CivicBudget\Http\Requests\Public\UpdatePublicProjectCorrectionRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
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
            'intent' => $request->isDraftSave() ? 'draft' : 'submit',
        ]);

        $data = $request->validated();
        $projectArea = $this->resolveProjectArea($data, $costLimitService);

        $project = Project::query()->create([
            'budget_edition_id' => $data['budget_edition_id'],
            'project_area_id' => $projectArea?->id,
            'category_id' => $data['category_id'] ?? null,
            'creator_id' => $request->user()?->id,
            'title' => $data['title'],
            'local' => $data['local'] ?? null,
            'localization' => $data['localization'] ?? null,
            'address' => $data['address'] ?? null,
            'plot' => $data['plot'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'map_lng_lat' => $data['map_lng_lat'] ?? null,
            'map_data' => $data['map_data'] ?? null,
            'description' => $data['description'] ?? null,
            'goal' => $data['goal'] ?? null,
            'argumentation' => $data['argumentation'] ?? null,
            'availability' => $data['availability'] ?? null,
            'recipients' => $data['recipients'] ?? null,
            'free_of_charge' => $data['free_of_charge'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'additional_cost' => $data['additional_cost'] ?? null,
            'contact_with' => $data['contact_with'] ?? null,
            'attachments_anonymized' => (bool) ($data['attachments_anonymized'] ?? false),
            'consent_to_change' => (bool) ($data['consent_to_change'] ?? false),
            'show_task_coauthors' => (bool) ($data['show_task_coauthors'] ?? true),
            'authors' => $request->authorSnapshot(),
            'status' => ProjectStatus::WorkingCopy,
            'is_support_list' => (bool) ($data['support_list'] ?? false),
            'cost' => collect($request->costItems())
                ->map(fn (array $costItem): string => $costItem['description'].': '.$costItem['amount'])
                ->implode(PHP_EOL),
            'cost_formatted' => collect($request->costItems())->sum('amount'),
        ]);
        $this->syncProjectCategory($project, $data['category_id'] ?? null);

        foreach ($request->costItems() as $costItem) {
            $project->costItems()->create($costItem);
        }

        try {
            $supportListFile = $request->file('support_list_file');

            if (! $supportListFile instanceof UploadedFile) {
                if (! $request->isDraftSave()) {
                    Log::warning('project.public_store.rejected_missing_support_file', [
                        'project_id' => $project->id,
                    ]);

                    return back()->withInput()->withErrors(['support_list_file' => 'Brak pliku listy poparcia.']);
                }
            } else {
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

                $project->forceFill([
                    'is_support_list' => true,
                ])->save();
            }

            $this->storeProjectFilesFromInput($request, $storeProjectFile, $project, 'owner_agreement_files', ProjectFileType::OwnerAgreement, true);
            $this->storeProjectFilesFromInput($request, $storeProjectFile, $project, 'map_files', ProjectFileType::Map);
            $this->storeProjectFilesFromInput($request, $storeProjectFile, $project, 'parent_agreement_files', ProjectFileType::ParentAgreement, true);
            $this->storeProjectFilesFromInput($request, $storeProjectFile, $project, 'attachment_files', ProjectFileType::Other);

            $syncProjectCoauthors->execute($project, $request->coauthors());

            if (! $request->isDraftSave()) {
                $submitProject->execute($project);
            }
        } catch (DomainException $exception) {
            Log::warning('project.public_store.rejected', [
                'project_id' => $project->id,
                'reason' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['project' => $exception->getMessage()]);
        }

        Log::info('project.public_store.success', [
            'project_id' => $project->id,
            'intent' => $request->isDraftSave() ? 'draft' : 'submit',
            'status' => $project->refresh()->status->value,
        ]);

        return redirect()
            ->route($request->isDraftSave() ? 'public.resident.projects' : 'public.projects.index')
            ->with('status', $request->isDraftSave() ? 'Kopia robocza projektu została zapisana.' : 'Projekt został zgłoszony.');
    }

    public function updateDraft(
        StorePublicProjectRequest $request,
        Project $project,
        StoreProjectFileAction $storeProjectFile,
        SyncProjectCoauthorsAction $syncProjectCoauthors,
        SubmitProjectAction $submitProject,
        ProjectCostLimitService $costLimitService,
    ): RedirectResponse {
        Log::info('project.public_draft_update.start', [
            'project_id' => $project->id,
            'actor_id' => $request->user()?->id,
            'intent' => $request->isDraftSave() ? 'draft' : 'submit',
        ]);

        abort_unless($project->creator_id === $request->user()?->id, 403);

        if ($project->status !== ProjectStatus::WorkingCopy) {
            Log::warning('project.public_draft_update.rejected_status', [
                'project_id' => $project->id,
                'actor_id' => $request->user()?->id,
                'status' => $project->status->value,
            ]);

            return back()->withErrors(['project' => 'Edytować można tylko kopię roboczą projektu.']);
        }

        $data = $request->validated();
        $projectArea = $this->resolveProjectArea($data, $costLimitService);

        $project->forceFill([
            'budget_edition_id' => $data['budget_edition_id'],
            'project_area_id' => $projectArea?->id,
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'local' => $data['local'] ?? null,
            'localization' => $data['localization'] ?? null,
            'address' => $data['address'] ?? null,
            'plot' => $data['plot'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'map_lng_lat' => $data['map_lng_lat'] ?? null,
            'map_data' => $data['map_data'] ?? null,
            'description' => $data['description'] ?? null,
            'goal' => $data['goal'] ?? null,
            'argumentation' => $data['argumentation'] ?? null,
            'availability' => $data['availability'] ?? null,
            'recipients' => $data['recipients'] ?? null,
            'free_of_charge' => $data['free_of_charge'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'additional_cost' => $data['additional_cost'] ?? null,
            'contact_with' => $data['contact_with'] ?? null,
            'attachments_anonymized' => (bool) ($data['attachments_anonymized'] ?? false),
            'consent_to_change' => (bool) ($data['consent_to_change'] ?? false),
            'show_task_coauthors' => (bool) ($data['show_task_coauthors'] ?? true),
            'authors' => $request->authorSnapshot(),
            'is_support_list' => (bool) ($data['support_list'] ?? false) || $project->files()->where('type', ProjectFileType::SupportList->value)->exists(),
            'cost' => collect($request->costItems())
                ->map(fn (array $costItem): string => $costItem['description'].': '.$costItem['amount'])
                ->implode(PHP_EOL),
            'cost_formatted' => collect($request->costItems())->sum('amount'),
        ])->save();

        $this->syncProjectCategory($project, $data['category_id'] ?? null);
        $this->replaceDraftCostItems($project, $request->costItems());

        try {
            $supportListFile = $request->file('support_list_file');
            if ($supportListFile instanceof UploadedFile) {
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

                $project->forceFill([
                    'is_support_list' => true,
                ])->save();
            }

            $this->storeProjectFilesFromInput($request, $storeProjectFile, $project, 'owner_agreement_files', ProjectFileType::OwnerAgreement, true);
            $this->storeProjectFilesFromInput($request, $storeProjectFile, $project, 'map_files', ProjectFileType::Map);
            $this->storeProjectFilesFromInput($request, $storeProjectFile, $project, 'parent_agreement_files', ProjectFileType::ParentAgreement, true);
            $this->storeProjectFilesFromInput($request, $storeProjectFile, $project, 'attachment_files', ProjectFileType::Other);

            $syncProjectCoauthors->execute($project, $request->coauthors());

            if (! $request->isDraftSave()) {
                $submitProject->execute($project);
            }
        } catch (DomainException $exception) {
            Log::warning('project.public_draft_update.rejected', [
                'project_id' => $project->id,
                'actor_id' => $request->user()?->id,
                'reason' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['project' => $exception->getMessage()]);
        }

        Log::info('project.public_draft_update.success', [
            'project_id' => $project->id,
            'actor_id' => $request->user()?->id,
            'intent' => $request->isDraftSave() ? 'draft' : 'submit',
            'status' => $project->refresh()->status->value,
        ]);

        return redirect()
            ->route($request->isDraftSave() ? 'public.resident.projects' : 'public.projects.index')
            ->with('status', $request->isDraftSave() ? 'Kopia robocza projektu została zapisana.' : 'Projekt został zgłoszony.');
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveProjectArea(array $data, ProjectCostLimitService $costLimitService): ?ProjectArea
    {
        if (! isset($data['project_area_id'])) {
            return null;
        }

        $projectArea = ProjectArea::query()->findOrFail($data['project_area_id']);

        if (! isset($data['local'])) {
            return $projectArea;
        }

        return $costLimitService->resolveSubmissionArea($projectArea, (int) $data['local']);
    }

    private function syncProjectCategory(Project $project, mixed $categoryId): void
    {
        if ($categoryId === null) {
            $project->categories()->sync([]);

            return;
        }

        $project->categories()->sync([(int) $categoryId]);
    }

    /**
     * @param  list<array{description: string, amount: float}>  $costItems
     */
    private function replaceDraftCostItems(Project $project, array $costItems): void
    {
        $project->costItems()->delete();

        foreach ($costItems as $costItem) {
            $project->costItems()->create(Arr::only($costItem, ['description', 'amount']));
        }
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
