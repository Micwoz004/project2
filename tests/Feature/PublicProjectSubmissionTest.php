<?php

use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Files\Models\ProjectFile;
use App\Domain\Projects\Actions\StartCorrectionAction;
use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('validates public project submission at the request boundary', function (): void {
    $this->from(route('public.projects.create'))
        ->post(route('public.projects.store'), [])
        ->assertRedirect(route('public.projects.create'))
        ->assertSessionHasErrors([
            'budget_edition_id',
            'project_area_id',
            'category_id',
            'title',
            'support_list',
            'support_list_file',
        ]);

    expect(Project::query()->count())->toBe(0);
});

it('creates a submitted project through the public endpoint', function (): void {
    Storage::fake('local');
    Storage::fake('public');
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $category = Category::query()->create(['name' => 'Zieleń']);

    $this->post(route('public.projects.store'), [
        'budget_edition_id' => $edition->id,
        'project_area_id' => $area->id,
        'category_id' => $category->id,
        'title' => 'Nowy park kieszonkowy',
        'localization' => 'Szczecin',
        'address' => 'Plac Andersa 1',
        'plot' => 'Działka 10/2',
        'lat' => '53.4285432',
        'lng' => '14.5528116',
        'map_lng_lat' => '14.5528116,53.4285432',
        'map_data' => json_encode(['type' => 'Point', 'coordinates' => [14.5528116, 53.4285432]]),
        'description' => 'Opis projektu',
        'goal' => 'Cel projektu',
        'argumentation' => 'Uzasadnienie',
        'availability' => 'Dostępność',
        'recipients' => 'Mieszkańcy',
        'free_of_charge' => 'Tak',
        'cost_description' => 'Zakup i montaż wyposażenia',
        'cost_amount' => 10000,
        'coauthors' => [[
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email' => 'anna@example.test',
            'read_confirm' => '1',
            'email_agree' => '1',
        ]],
        'support_list' => '1',
        'support_list_file' => UploadedFile::fake()->create('lista-poparcia.pdf', 128, 'application/pdf'),
        'owner_agreement_files' => [
            UploadedFile::fake()->create('zgoda-wlasciciela.pdf', 64, 'application/pdf'),
        ],
        'map_files' => [
            UploadedFile::fake()->create('mapa.png', 64, 'image/png'),
        ],
        'attachment_files' => [
            UploadedFile::fake()->create('opis.pdf', 64, 'application/pdf'),
        ],
    ])->assertRedirect(route('public.projects.index'));

    $project = Project::query()->firstOrFail();
    $supportListFile = $project->files()->where('type', ProjectFileType::SupportList)->firstOrFail();
    $ownerAgreementFile = $project->files()->where('type', ProjectFileType::OwnerAgreement)->firstOrFail();
    $mapFile = $project->files()->where('type', ProjectFileType::Map)->firstOrFail();
    $attachmentFile = $project->files()->where('type', ProjectFileType::Other)->firstOrFail();

    Storage::disk('local')->assertExists($supportListFile->stored_name);
    Storage::disk('local')->assertExists($ownerAgreementFile->stored_name);
    Storage::disk('public')->assertExists($mapFile->stored_name);
    Storage::disk('public')->assertExists($attachmentFile->stored_name);

    expect($project->status)->toBe(ProjectStatus::Submitted)
        ->and($project->address)->toBe('Plac Andersa 1')
        ->and($project->plot)->toBe('Działka 10/2')
        ->and($project->lat)->toBe('53.4285432')
        ->and($project->lng)->toBe('14.5528116')
        ->and($project->map_lng_lat)->toBe('14.5528116,53.4285432')
        ->and($project->map_data)->toBe(['type' => 'Point', 'coordinates' => [14.5528116, 53.4285432]])
        ->and($project->costItems()->count())->toBe(1)
        ->and($project->coauthors()->count())->toBe(1)
        ->and($project->coauthors()->firstOrFail()->email)->toBe('anna@example.test')
        ->and($project->files()->count())->toBe(4)
        ->and($project->versions()->count())->toBe(1)
        ->and($project->category_id)->toBe($category->id)
        ->and($project->categories()->pluck('categories.id')->all())->toBe([$category->id])
        ->and($supportListFile->is_private)->toBeTrue()
        ->and($ownerAgreementFile->is_private)->toBeTrue()
        ->and($mapFile->is_private)->toBeFalse()
        ->and($attachmentFile->is_private)->toBeFalse()
        ->and($supportListFile->is_task_form_attachment)->toBeTrue()
        ->and($ownerAgreementFile->is_task_form_attachment)->toBeTrue()
        ->and($mapFile->is_task_form_attachment)->toBeTrue()
        ->and($attachmentFile->is_task_form_attachment)->toBeTrue()
        ->and($supportListFile->original_name)->toBe('lista-poparcia.pdf');
});

it('rejects public project submission when coauthor has no public contact consent', function (): void {
    Storage::fake('local');
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $category = Category::query()->create(['name' => 'Zieleń']);

    $this->from(route('public.projects.create'))
        ->post(route('public.projects.store'), [
            'budget_edition_id' => $edition->id,
            'project_area_id' => $area->id,
            'category_id' => $category->id,
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
            'coauthors' => [[
                'first_name' => 'Anna',
                'last_name' => 'Nowak',
                'email' => 'anna@example.test',
                'read_confirm' => '1',
            ]],
            'support_list' => '1',
            'support_list_file' => UploadedFile::fake()->create('lista-poparcia.pdf', 128, 'application/pdf'),
        ])
        ->assertRedirect(route('public.projects.create'))
        ->assertSessionHasErrors(['project']);

    expect(Project::query()->where('status', ProjectStatus::Submitted->value)->count())->toBe(0);
});

it('returns not found for hidden or non-public project details', function (): void {
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Submitted,
    ]));

    $this->get(route('public.projects.show', $project))->assertNotFound();
});

it('lets the project author apply an active correction through the public endpoint', function (): void {
    $author = User::factory()->create();
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $category = Category::query()->create(['name' => 'Zieleń']);
    $newCategory = Category::query()->create(['name' => 'Sport']);
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'creator_id' => $author->id,
        'category_id' => $category->id,
        'status' => ProjectStatus::Submitted,
        'is_support_list' => true,
        'submitted_at' => now()->subDay(),
    ]));
    $project->categories()->sync([$category->id]);
    $project->costItems()->create([
        'description' => 'Prace projektowe',
        'amount' => 1000,
    ]);
    ProjectFile::query()->create([
        'project_id' => $project->id,
        'stored_name' => 'support.pdf',
        'original_name' => 'support.pdf',
        'type' => ProjectFileType::SupportList,
    ]);

    app(StartCorrectionAction::class)->execute(
        $project,
        $author,
        [ProjectCorrectionField::Title, ProjectCorrectionField::Category, ProjectCorrectionField::Description, ProjectCorrectionField::Cost],
        'Popraw tytuł i kategorię.',
        now()->addDay(),
    );

    $this->actingAs($author)
        ->get(route('public.projects.corrections.edit', $project))
        ->assertOk()
        ->assertSee('Korekta projektu')
        ->assertSee('Popraw tytuł i kategorię.');

    $this->actingAs($author)
        ->put(route('public.projects.corrections.update', $project), [
            'title' => 'Tytuł po korekcie',
            'category_id' => $newCategory->id,
            'description' => 'Opis po korekcie',
            'goal' => 'Tego pola nie wolno poprawić',
            'cost_items' => [
                ['description' => 'Nowy koszt', 'amount' => 2500],
            ],
        ])
        ->assertRedirect(route('public.projects.index'));

    $project->refresh();

    expect($project->title)->toBe('Tytuł po korekcie')
        ->and($project->category_id)->toBe($newCategory->id)
        ->and($project->categories()->pluck('categories.id')->all())->toBe([$newCategory->id])
        ->and($project->description)->toBe('Opis po korekcie')
        ->and($project->goal)->toBe('Cel projektu')
        ->and($project->costItems()->firstOrFail()->amount)->toBe('2500.00')
        ->and($project->need_correction)->toBeFalse()
        ->and($project->versions()->count())->toBe(1);
});

it('lets the project author apply an attachment-only correction through the public endpoint', function (): void {
    Storage::fake('public');
    $author = User::factory()->create();
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'creator_id' => $author->id,
        'status' => ProjectStatus::Submitted,
        'is_support_list' => true,
        'submitted_at' => now()->subDay(),
    ]));
    $project->costItems()->create([
        'description' => 'Prace projektowe',
        'amount' => 1000,
    ]);
    ProjectFile::query()->create([
        'project_id' => $project->id,
        'stored_name' => 'support.pdf',
        'original_name' => 'support.pdf',
        'type' => ProjectFileType::SupportList,
    ]);

    app(StartCorrectionAction::class)->execute(
        $project,
        $author,
        [ProjectCorrectionField::MapAttachment],
        'Uzupełnij mapę.',
        now()->addDay(),
    );

    $this->actingAs($author)
        ->put(route('public.projects.corrections.update', $project), [
            'map_files' => [
                UploadedFile::fake()->create('mapa.png', 64, 'image/png'),
            ],
        ])
        ->assertRedirect(route('public.projects.index'));

    $mapFile = $project->refresh()->files()->where('type', ProjectFileType::Map)->firstOrFail();

    Storage::disk('public')->assertExists($mapFile->stored_name);

    expect($mapFile->is_task_form_attachment)->toBeTrue()
        ->and($project->need_correction)->toBeFalse()
        ->and($project->versions()->count())->toBe(1);
});

it('forbids public correction access for other users', function (): void {
    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'creator_id' => $author->id,
        'status' => ProjectStatus::Submitted,
        'need_correction' => true,
        'correction_start_time' => now()->subHour(),
        'correction_end_time' => now()->addDay(),
    ]));

    $this->actingAs($otherUser)
        ->get(route('public.projects.corrections.edit', $project))
        ->assertForbidden();
});
