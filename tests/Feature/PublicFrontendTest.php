<?php

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Settings\Models\CostGuideItem;
use App\Domain\Settings\Models\PublicAnnouncement;
use App\Domain\Settings\Models\PublicPage;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Filament\Resources\CostGuideItems\CostGuideItemResource;
use App\Filament\Resources\PublicAnnouncements\PublicAnnouncementResource;
use App\Filament\Resources\PublicPages\PublicPageResource;
use App\Models\User;

it('renders public homepage with current edition schedule and published announcements', function (): void {
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    project($edition->id, $area->id, [
        'title' => 'Skwer sąsiedzki',
        'status' => ProjectStatus::Submitted,
    ]);
    PublicAnnouncement::query()->create([
        'title' => 'Start naboru projektów',
        'slug' => 'start-naboru',
        'lead' => 'Można zgłaszać projekty do nowej edycji.',
        'body' => '<p>Treść ogłoszenia.</p>',
        'published_at' => now()->subHour(),
        'is_published' => true,
    ]);

    $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('bo-spa-root')
        ->assertSee('window.BO_SPA', false)
        ->assertSee('Start naboru projektów')
        ->assertSee('Projekty');
});

it('renders cost guide items from admin content', function (): void {
    CostGuideItem::query()->create([
        'label' => 'Kosz parkowy premium',
        'price_range' => '3-8 tys. zł',
        'is_published' => true,
        'sort' => 10,
    ]);
    CostGuideItem::query()->create([
        'label' => 'Robocza pozycja',
        'price_range' => '1 zł',
        'is_published' => false,
        'sort' => 20,
    ]);

    $response = $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('Kosz parkowy premium')
        ->assertDontSee('Robocza pozycja');

    expect($response->getContent())->toContain('3-8 tys. z\\u0142');
});

it('shows only published public announcements', function (): void {
    PublicAnnouncement::query()->create([
        'title' => 'Widoczne ogłoszenie',
        'slug' => 'widoczne-ogloszenie',
        'body' => '<p>Treść widoczna.</p>',
        'published_at' => now()->subDay(),
        'is_published' => true,
    ]);
    PublicAnnouncement::query()->create([
        'title' => 'Robocze ogłoszenie',
        'slug' => 'robocze-ogloszenie',
        'body' => '<p>Treść robocza.</p>',
        'published_at' => now()->subDay(),
        'is_published' => false,
    ]);

    $this->get(route('public.announcements.index'))
        ->assertOk()
        ->assertSee('Widoczne ogłoszenie')
        ->assertDontSee('Robocze ogłoszenie');

    $this->get(route('public.announcements.show', 'robocze-ogloszenie'))
        ->assertNotFound();
});

it('uses database public page over static fallback', function (): void {
    PublicPage::query()->create([
        'title' => 'Harmonogram z panelu',
        'slug' => 'harmonogram',
        'body' => '<p>Treść z bazy danych.</p>',
        'is_published' => true,
    ]);

    $this->get(route('public.info.show', 'harmonogram'))
        ->assertOk()
        ->assertSee('bo-spa-root')
        ->assertSee('Harmonogram z panelu')
        ->assertDontSee('Dokładne daty są prezentowane');
});

it('renders static information fallback when public page is not configured', function (): void {
    $this->get(route('public.info.show', 'o-budzecie'))
        ->assertOk()
        ->assertSee('bo-spa-root')
        ->assertSee('window.BO_SPA', false);
});

it('guards public content resources with settings permissions', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    PublicAnnouncement::query()->create([
        'title' => 'Ogłoszenie admin',
        'slug' => 'ogloszenie-admin',
        'body' => '<p>Treść.</p>',
        'is_published' => true,
    ]);
    PublicPage::query()->create([
        'title' => 'Strona admin',
        'slug' => 'strona-admin',
        'body' => '<p>Treść.</p>',
        'is_published' => true,
    ]);
    CostGuideItem::query()->create([
        'label' => 'Pozycja admin',
        'price_range' => '1-2 tys. zł',
        'is_published' => true,
    ]);

    $manager = User::factory()->create(['status' => true]);
    $manager->givePermissionTo(SystemPermission::AdminAccess->value);
    $manager->givePermissionTo(SystemPermission::SettingsManage->value);
    $viewer = User::factory()->create(['status' => true]);
    $viewer->givePermissionTo(SystemPermission::AdminAccess->value);

    $this->actingAs($manager)
        ->get(PublicAnnouncementResource::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('Ogłoszenie admin');

    $this->actingAs($manager)
        ->get(PublicPageResource::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('Strona admin');

    $this->actingAs($manager)
        ->get(CostGuideItemResource::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('Pozycja admin');

    $this->actingAs($viewer)
        ->get(PublicAnnouncementResource::getUrl(panel: 'admin'))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->get(PublicPageResource::getUrl(panel: 'admin'))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->get(CostGuideItemResource::getUrl(panel: 'admin'))
        ->assertForbidden();
});
