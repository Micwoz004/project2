<?php

use App\Domain\Dictionaries\Enums\DictionaryKind;
use App\Domain\Dictionaries\Models\DictionaryEntry;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Services\ProjectAreaCatalog;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Filament\Resources\DictionaryEntries\DictionaryEntryResource;
use App\Models\User;

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

it('guards dictionary entries resource with dictionary permissions', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    DictionaryEntry::query()->create([
        'kind' => DictionaryKind::FirstName,
        'value' => 'JAN',
        'active' => true,
    ]);

    $manager = User::factory()->create(['status' => true]);
    $manager->givePermissionTo(SystemPermission::AdminAccess->value);
    $manager->givePermissionTo(SystemPermission::DictionariesManage->value);
    $viewer = User::factory()->create(['status' => true]);
    $viewer->givePermissionTo(SystemPermission::AdminAccess->value);

    $this->actingAs($manager)
        ->get(DictionaryEntryResource::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('JAN')
        ->assertSee('Imię');

    $this->actingAs($viewer)
        ->get(DictionaryEntryResource::getUrl(panel: 'admin'))
        ->assertForbidden();
});
