<?php

use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Services\ProjectAreaCatalog;

it('returns local areas ordered like legacy list data', function (): void {
    $citywide = ProjectArea::query()->create(areaAttributes([
        'name' => 'Ogólnomiejskie',
        'symbol' => 'OGM',
        'is_local' => false,
    ]));
    $second = ProjectArea::query()->create(areaAttributes([
        'name' => 'Żelechowa',
        'symbol' => 'ZEL',
    ]));
    $first = ProjectArea::query()->create(areaAttributes([
        'name' => 'Arkońskie',
        'symbol' => 'ARK',
    ]));

    $areas = app(ProjectAreaCatalog::class)->localAreas();

    expect($areas->pluck('id')->all())->toBe([$first->id, $second->id])
        ->and(app(ProjectAreaCatalog::class)->citywideArea()?->id)->toBe($citywide->id);
});

it('keeps category relationship with projects through pivot table', function (): void {
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $category = Category::query()->create(['name' => 'Zieleń']);
    $project = project($edition->id, $area->id);

    $project->categories()->attach($category);

    expect($category->projects()->pluck('projects.id')->all())->toBe([$project->id]);
});

it('registers admin resources for areas and categories', function (): void {
    $this->get('/admin/project-areas')->assertRedirect('/admin/login');
    $this->get('/admin/categories')->assertRedirect('/admin/login');
});
