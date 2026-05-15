<?php

use App\Domain\Files\Actions\MarkProjectAttachmentsAnonymizedAction;
use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;

it('lists only publicly visible projects ordered by legacy drawn number', function (): void {
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());

    $third = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'title' => 'Trzeci projekt',
        'status' => ProjectStatus::Picked,
        'number_drawn' => 30,
    ]));
    $first = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'title' => 'Pierwszy projekt',
        'status' => ProjectStatus::Picked,
        'number_drawn' => 10,
    ]));
    $hidden = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'title' => 'Ukryty projekt',
        'status' => ProjectStatus::Picked,
        'is_hidden' => true,
        'number_drawn' => 20,
    ]));
    $submitted = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'title' => 'Projekt zgłoszony',
        'status' => ProjectStatus::Submitted,
    ]));

    $response = $this->get(route('public.projects.index'));

    $response->assertOk()
        ->assertSeeInOrder([$first->title, $third->title])
        ->assertDontSee($hidden->title)
        ->assertDontSee($submitted->title);
});

it('filters public projects by edition area category and search term', function (): void {
    $edition = budgetEdition();
    $otherEdition = budgetEdition([
        'propose_start' => now()->addMonths(2),
        'propose_end' => now()->addMonths(3),
        'pre_voting_verification_end' => now()->addMonths(4),
        'voting_start' => now()->addMonths(5),
        'voting_end' => now()->addMonths(6),
        'post_voting_verification_end' => now()->addMonths(7),
        'result_announcement_end' => now()->addMonths(8),
    ]);
    $area = ProjectArea::query()->create(areaAttributes());
    $otherArea = ProjectArea::query()->create(areaAttributes([
        'name' => 'Inny obszar',
        'symbol' => 'L2',
    ]));
    $category = Category::query()->create(['name' => 'Zieleń']);
    $otherCategory = Category::query()->create(['name' => 'Sport']);

    $matching = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'title' => 'Zielony skwer przy ulicy',
        'status' => ProjectStatus::Picked,
        'category_id' => $category->id,
        'number_drawn' => 1,
    ]));
    Project::query()->create(projectAttributes($otherEdition->id, $area->id, [
        'title' => 'Zielony skwer w innej edycji',
        'status' => ProjectStatus::Picked,
        'category_id' => $category->id,
        'number_drawn' => 2,
    ]));
    Project::query()->create(projectAttributes($edition->id, $otherArea->id, [
        'title' => 'Zielony skwer w innym obszarze',
        'status' => ProjectStatus::Picked,
        'category_id' => $category->id,
        'number_drawn' => 3,
    ]));
    Project::query()->create(projectAttributes($edition->id, $area->id, [
        'title' => 'Boisko szkolne',
        'status' => ProjectStatus::Picked,
        'category_id' => $otherCategory->id,
        'number_drawn' => 4,
    ]));

    $response = $this->get(route('public.projects.index', [
        'budget_edition_id' => $edition->id,
        'area_id' => $area->id,
        'category_id' => $category->id,
        'q' => 'skwer',
    ]));

    $response->assertOk()
        ->assertSee($matching->title)
        ->assertDontSee('Zielony skwer w innej edycji')
        ->assertDontSee('Zielony skwer w innym obszarze')
        ->assertDontSee('Boisko szkolne');
});

it('shows only public files after project attachments are anonymized', function (): void {
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'title' => 'Projekt z załącznikami',
        'status' => ProjectStatus::Picked,
    ]));
    $project->files()->create([
        'stored_name' => 'projects/1/attachments/publiczny.pdf',
        'original_name' => 'publiczny.pdf',
        'type' => ProjectFileType::Other,
        'is_private' => false,
    ]);
    $project->files()->create([
        'stored_name' => 'projects/1/attachments/prywatny.pdf',
        'original_name' => 'prywatny.pdf',
        'type' => ProjectFileType::OwnerAgreement,
        'is_private' => true,
    ]);

    $this->get(route('public.projects.show', $project))
        ->assertOk()
        ->assertDontSee('publiczny.pdf')
        ->assertDontSee('prywatny.pdf');

    app(MarkProjectAttachmentsAnonymizedAction::class)->execute($project);

    $this->get(route('public.projects.show', $project))
        ->assertOk()
        ->assertSee('publiczny.pdf')
        ->assertDontSee('prywatny.pdf');
});
