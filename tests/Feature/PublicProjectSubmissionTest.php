<?php

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('validates public project submission at the request boundary', function (): void {
    $this->from(route('public.projects.create'))
        ->post(route('public.projects.store'), [])
        ->assertRedirect(route('public.projects.create'))
        ->assertSessionHasErrors([
            'budget_edition_id',
            'project_area_id',
            'title',
            'support_list',
            'support_list_file',
        ]);

    expect(Project::query()->count())->toBe(0);
});

it('creates a submitted project through the public endpoint', function (): void {
    Storage::fake('local');
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());

    $this->post(route('public.projects.store'), [
        'budget_edition_id' => $edition->id,
        'project_area_id' => $area->id,
        'title' => 'Nowy park kieszonkowy',
        'localization' => 'Szczecin',
        'description' => 'Opis projektu',
        'goal' => 'Cel projektu',
        'argumentation' => 'Uzasadnienie',
        'availability' => 'Dostępność',
        'recipients' => 'Mieszkańcy',
        'free_of_charge' => 'Tak',
        'cost_description' => 'Zakup i montaż wyposażenia',
        'cost_amount' => 10000,
        'support_list' => '1',
        'support_list_file' => UploadedFile::fake()->create('lista-poparcia.pdf', 128, 'application/pdf'),
    ])->assertRedirect(route('public.projects.index'));

    $project = Project::query()->firstOrFail();
    $supportListFile = $project->files()->firstOrFail();

    Storage::disk('local')->assertExists($supportListFile->stored_name);

    expect($project->status)->toBe(ProjectStatus::Submitted)
        ->and($project->costItems()->count())->toBe(1)
        ->and($project->files()->count())->toBe(1)
        ->and($project->versions()->count())->toBe(1)
        ->and($supportListFile->is_private)->toBeTrue()
        ->and($supportListFile->is_task_form_attachment)->toBeTrue()
        ->and($supportListFile->original_name)->toBe('lista-poparcia.pdf');
});

it('returns not found for hidden or non-public project details', function (): void {
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Submitted,
    ]));

    $this->get(route('public.projects.show', $project))->assertNotFound();
});
