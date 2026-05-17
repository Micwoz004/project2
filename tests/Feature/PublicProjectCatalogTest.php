<?php

use App\Domain\Communications\Jobs\SendProjectNotificationJob;
use App\Domain\Communications\Models\ProjectPublicComment;
use App\Domain\Files\Actions\MarkProjectAttachmentsAnonymizedAction;
use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemRole;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

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

it('shows map view only for public projects with coordinates', function (): void {
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());

    $withLatLng = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'title' => 'Projekt z punktem',
        'status' => ProjectStatus::Picked,
        'lat' => 53.4285432,
        'lng' => 14.5528116,
        'number_drawn' => 1,
    ]));
    $withMapData = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'title' => 'Projekt z mapData',
        'status' => ProjectStatus::Picked,
        'map_data' => [[
            'type' => 'marker',
            'coords' => ['lat' => 53.4, 'lng' => 14.6],
        ]],
        'number_drawn' => 2,
    ]));
    Project::query()->create(projectAttributes($edition->id, $area->id, [
        'title' => 'Projekt bez mapy',
        'status' => ProjectStatus::Picked,
        'number_drawn' => 3,
    ]));
    Project::query()->create(projectAttributes($edition->id, $area->id, [
        'title' => 'Projekt ukryty na mapie',
        'status' => ProjectStatus::Picked,
        'lat' => 53.5,
        'lng' => 14.7,
        'is_hidden' => true,
    ]));

    $response = $this->get(route('public.projects.map'));

    $response->assertOk()
        ->assertSee($withLatLng->title)
        ->assertSee($withMapData->title)
        ->assertSee('53.4285432, 14.5528116')
        ->assertSee('53.4000000, 14.6000000')
        ->assertDontSee('Projekt bez mapy')
        ->assertDontSee('Projekt ukryty na mapie');
});

it('shows public project comments according to legacy visibility rules', function (): void {
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $creator = User::factory()->create();
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));

    ProjectPublicComment::query()->create([
        'project_id' => $project->id,
        'created_by_id' => $creator->id,
        'content' => 'Komentarz widoczny publicznie',
        'moderated' => true,
    ]);
    ProjectPublicComment::query()->create([
        'project_id' => $project->id,
        'created_by_id' => $creator->id,
        'content' => 'Komentarz oczekujący',
        'moderated' => false,
    ]);
    ProjectPublicComment::query()->create([
        'project_id' => $project->id,
        'created_by_id' => $creator->id,
        'content' => 'Komentarz ukryty przez autora',
        'hidden' => true,
        'moderated' => true,
    ]);
    ProjectPublicComment::query()->create([
        'project_id' => $project->id,
        'created_by_id' => $creator->id,
        'content' => 'Komentarz ukryty administracyjnie',
        'admin_hidden' => true,
        'moderated' => true,
    ]);

    $this->get(route('public.projects.show', $project))
        ->assertOk()
        ->assertSee('Komentarz widoczny publicznie')
        ->assertDontSee('Komentarz oczekujący')
        ->assertDontSee('Komentarz ukryty przez autora')
        ->assertDontSee('Komentarz ukryty administracyjnie');
});

it('allows applicants to add edit and hide public project comments through public routes', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();
    Bus::fake();

    $author = User::factory()->create();
    $applicant = User::factory()->create();
    $applicant->assignRole(SystemRole::Applicant->value);
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'creator_id' => $author->id,
        'status' => ProjectStatus::Picked,
    ]));

    $this->actingAs($applicant)
        ->post(route('public.projects.comments.store', $project), [
            'content' => ' Nowy publiczny komentarz ',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $comment = ProjectPublicComment::query()->where('project_id', $project->id)->firstOrFail();

    expect($comment->created_by_id)->toBe($applicant->id)
        ->and($comment->content)->toBe('Nowy publiczny komentarz')
        ->and($comment->moderated)->toBeTrue();

    $this->actingAs($applicant)
        ->put(route('public.projects.comments.update', [$project, $comment]), [
            'content' => 'Po edycji',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($applicant)
        ->patch(route('public.projects.comments.toggle-hidden', [$project, $comment]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $comment->refresh();

    expect($comment->content)->toBe('Po edycji')
        ->and($comment->hidden)->toBeTrue();

    $this->actingAs($applicant)
        ->get(route('public.projects.show', $project))
        ->assertOk()
        ->assertSee('Po edycji')
        ->assertSee('Ukryty');

    Bus::assertDispatched(SendProjectNotificationJob::class);
});

it('rejects public comment routes for users without applicant role', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $user = User::factory()->create();
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));

    $this->actingAs($user)
        ->post(route('public.projects.comments.store', $project), [
            'content' => 'Komentarz bez roli',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('comment');

    expect(ProjectPublicComment::query()->count())->toBe(0);
});
