<?php

use App\Products\CivicBudget\Domain\Files\Enums\ProjectFileType;
use App\Products\CivicBudget\Domain\Files\Models\ProjectFile;
use App\Products\CivicBudget\Domain\Projects\Actions\StartCorrectionAction;
use App\Products\CivicBudget\Domain\Projects\Enums\ProjectCorrectionField;
use App\Products\CivicBudget\Domain\Projects\Enums\ProjectStatus;
use App\Products\CivicBudget\Domain\Projects\Models\Category;
use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Products\CivicBudget\Domain\Projects\Models\ProjectArea;
use App\Products\CivicBudget\Domain\Projects\Support\LegacyProjectFormText;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function verifiedResident(array $overrides = []): User
{
    return User::factory()->create([
        'status' => true,
        ...$overrides,
    ]);
}

it('redirects guests away from project submission', function (): void {
    $this->get(route('public.projects.create'))
        ->assertRedirect(route('login'));

    $this->post(route('public.projects.store'), [])
        ->assertRedirect(route('login'));

    expect(Project::query()->count())->toBe(0);
});

it('renders legacy project submission statement texts', function (): void {
    budgetEdition();
    ProjectArea::query()->create(areaAttributes());
    Category::query()->create(['name' => 'Zieleń']);

    $statements = LegacyProjectFormText::publicSubmissionStatements();

    $this->actingAs(verifiedResident())
        ->get(route('public.projects.create'))
        ->assertOk()
        ->assertSee($statements['contact_publication_hint'], false)
        ->assertSee($statements['regulation_confirmation'], false)
        ->assertSee($statements['attachments_anonymized'], false)
        ->assertSee($statements['consent_to_change'], false);
});

it('validates public project submission at the request boundary', function (): void {
    $this->actingAs(verifiedResident())
        ->from(route('public.projects.create'))
        ->post(route('public.projects.store'), [])
        ->assertRedirect(route('public.projects.create'))
        ->assertSessionHasErrors([
            'budget_edition_id',
            'project_area_id',
            'category_id',
            'local',
            'author_first_name',
            'author_last_name',
            'author_email',
            'author_read_confirm',
            'contact_with',
            'title',
            'map_data',
            'cost_items',
            'attachments_anonymized',
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
    $resident = verifiedResident();

    $this->actingAs($resident)->post(route('public.projects.store'), [
        'budget_edition_id' => $edition->id,
        'project_area_id' => $area->id,
        'category_id' => $category->id,
        'local' => 1,
        'author_first_name' => 'Piotr',
        'author_last_name' => 'Kowalski',
        'author_email' => 'piotr@example.test',
        'author_phone' => '500600700',
        'author_street' => 'Jasne Błonia',
        'author_house_no' => '1',
        'author_flat_no' => '2',
        'author_post_code' => '70-001',
        'author_city' => 'Szczecin',
        'author_email_agree' => '1',
        'author_personal_data_agree' => '1',
        'author_read_confirm' => '1',
        'contact_with' => 1,
        'title' => 'Nowy park kieszonkowy',
        'short_description' => 'Krótki opis projektu.',
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
        'additional_cost' => 'Koszty utrzymania zieleni.',
        'cost_items' => [
            ['description' => 'Zakup i montaż wyposażenia', 'amount' => 10000],
            ['description' => 'Nasadzenia', 'amount' => 5000],
        ],
        'consent_to_change' => '1',
        'show_task_coauthors' => '1',
        'attachments_anonymized' => '1',
        'coauthors' => [[
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email' => 'anna@example.test',
            'street' => 'Różana',
            'house_no' => '3',
            'flat_no' => '4',
            'post_code' => '70-002',
            'city' => 'Szczecin',
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
        ->and($project->creator_id)->toBe($resident->id)
        ->and($project->address)->toBe('Plac Andersa 1')
        ->and($project->plot)->toBe('Działka 10/2')
        ->and($project->lat)->toBe('53.4285432')
        ->and($project->lng)->toBe('14.5528116')
        ->and($project->map_lng_lat)->toBe('14.5528116,53.4285432')
        ->and($project->map_data)->toBe(['type' => 'Point', 'coordinates' => [14.5528116, 53.4285432]])
        ->and($project->local)->toBe(1)
        ->and($project->short_description)->toBe('Krótki opis projektu.')
        ->and($project->additional_cost)->toBe('Koszty utrzymania zieleni.')
        ->and($project->contact_with)->toBe(1)
        ->and($project->attachments_anonymized)->toBeTrue()
        ->and($project->consent_to_change)->toBeTrue()
        ->and($project->authors['email'])->toBe('piotr@example.test')
        ->and($project->costItems()->count())->toBe(2)
        ->and($project->cost_formatted)->toBe('15000.00')
        ->and($project->coauthors()->count())->toBe(1)
        ->and($project->coauthors()->firstOrFail()->email)->toBe('anna@example.test')
        ->and($project->coauthors()->firstOrFail()->street)->toBe('Różana')
        ->and($project->coauthors()->firstOrFail()->house_no)->toBe('3')
        ->and($project->coauthors()->firstOrFail()->flat_no)->toBe('4')
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

it('saves a public project as working copy without submitting it to the office', function (): void {
    $edition = budgetEdition();
    $resident = verifiedResident();

    $this->actingAs($resident)->post(route('public.projects.store'), [
        '_intent' => 'draft',
        'budget_edition_id' => $edition->id,
        'title' => 'Szkic projektu mieszkańca',
    ])->assertRedirect(route('public.resident.projects'));

    $project = Project::query()->firstOrFail();

    expect($project->status)->toBe(ProjectStatus::WorkingCopy)
        ->and($project->creator_id)->toBe($resident->id)
        ->and($project->submitted_at)->toBeNull()
        ->and($project->number)->toBeNull()
        ->and($project->versions()->count())->toBe(0);

    $this->actingAs($resident)
        ->get(route('public.resident.projects.edit', $project))
        ->assertOk()
        ->assertSee('bo-spa-root')
        ->assertSee('Szkic projektu mieszka\\u0144ca', false);
});

it('updates a draft from the resident form and can submit it later', function (): void {
    Storage::fake('local');
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $category = Category::query()->create(['name' => 'Zieleń']);
    $resident = verifiedResident();

    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'creator_id' => $resident->id,
        'budget_edition_id' => $edition->id,
        'category_id' => $category->id,
        'status' => ProjectStatus::WorkingCopy,
        'title' => 'Roboczy tytuł',
    ]));

    $this->actingAs($resident)->put(route('public.resident.projects.update', $project), [
        '_intent' => 'draft',
        'budget_edition_id' => $edition->id,
        'project_area_id' => $area->id,
        'category_id' => $category->id,
        'local' => 1,
        'title' => 'Zmieniony roboczy tytuł',
        'description' => 'Roboczy opis',
        'cost_items' => [
            ['description' => 'Roboczy koszt', 'amount' => 1000],
        ],
    ])->assertRedirect(route('public.resident.projects'));

    $project->refresh();

    expect($project->status)->toBe(ProjectStatus::WorkingCopy)
        ->and($project->title)->toBe('Zmieniony roboczy tytuł')
        ->and($project->description)->toBe('Roboczy opis')
        ->and($project->costItems()->firstOrFail()->description)->toBe('Roboczy koszt')
        ->and($project->versions()->count())->toBe(0);

    $this->actingAs($resident)->put(route('public.resident.projects.update', $project), [
        '_intent' => 'submit',
        'budget_edition_id' => $edition->id,
        'project_area_id' => $area->id,
        'category_id' => $category->id,
        'local' => 1,
        'author_first_name' => 'Piotr',
        'author_last_name' => 'Kowalski',
        'author_email' => 'piotr@example.test',
        'author_email_agree' => '1',
        'author_read_confirm' => '1',
        'contact_with' => 1,
        'title' => 'Gotowy projekt',
        'localization' => 'Szczecin',
        'map_data' => json_encode(['type' => 'Point', 'coordinates' => [14.5528116, 53.4285432]]),
        'description' => 'Opis projektu',
        'goal' => 'Cel projektu',
        'argumentation' => 'Uzasadnienie',
        'availability' => 'Dostępność',
        'recipients' => 'Mieszkańcy',
        'free_of_charge' => 'Tak',
        'cost_items' => [
            ['description' => 'Zakup i montaż wyposażenia', 'amount' => 10000],
        ],
        'attachments_anonymized' => '1',
        'support_list' => '1',
        'support_list_file' => UploadedFile::fake()->create('lista-poparcia.pdf', 128, 'application/pdf'),
    ])->assertRedirect(route('public.projects.index'));

    $project->refresh();

    expect($project->status)->toBe(ProjectStatus::Submitted)
        ->and($project->submitted_at)->not->toBeNull()
        ->and($project->versions()->count())->toBe(1)
        ->and($project->files()->where('type', ProjectFileType::SupportList)->exists())->toBeTrue();
});

it('lets the project author download submission card pdf after submitting to the office', function (): void {
    $author = verifiedResident();
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $category = Category::query()->create(['name' => 'Zieleń']);
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'creator_id' => $author->id,
        'category_id' => $category->id,
        'status' => ProjectStatus::Submitted,
        'submitted_at' => now()->subHour(),
        'number_drawn' => 'P1/0001',
        'authors' => [
            'first_name' => 'Piotr',
            'last_name' => 'Kowalski',
            'email' => 'piotr@example.test',
            'phone' => '500600700',
            'street' => 'Jasne Błonia',
            'house_no' => '1',
            'flat_no' => '2',
            'post_code' => '70-001',
            'city' => 'Szczecin',
        ],
        'short_description' => 'Krótki opis projektu.',
        'contact_with' => true,
        'attachments_anonymized' => true,
        'consent_to_change' => true,
    ]));
    $project->categories()->sync([$category->id]);
    $project->costItems()->create([
        'description' => 'Zakup i montaż wyposażenia',
        'amount' => 10000,
    ]);
    ProjectFile::query()->create([
        'project_id' => $project->id,
        'stored_name' => 'support.pdf',
        'original_name' => 'lista-poparcia.pdf',
        'type' => ProjectFileType::SupportList,
    ]);

    $this->actingAs($author)
        ->get(route('public.resident.projects'))
        ->assertOk()
        ->assertSee('karta-zgloszeniowa.pdf', false);

    $response = $this->actingAs($author)
        ->get(route('public.resident.projects.submission-card', $project))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain('karta-zgloszeniowa-projekt-P1-0001.pdf')
        ->and($response->getContent())->toStartWith('%PDF');
});

it('does not generate submission card pdf for working copies', function (): void {
    $author = verifiedResident();
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'creator_id' => $author->id,
        'status' => ProjectStatus::WorkingCopy,
        'submitted_at' => null,
    ]));

    $this->actingAs($author)
        ->get(route('public.resident.projects.submission-card', $project))
        ->assertNotFound();
});

it('forbids submission card pdf download for other residents', function (): void {
    $author = verifiedResident();
    $otherResident = verifiedResident();
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'creator_id' => $author->id,
        'status' => ProjectStatus::Submitted,
        'submitted_at' => now()->subHour(),
    ]));

    $this->actingAs($otherResident)
        ->get(route('public.resident.projects.submission-card', $project))
        ->assertForbidden();
});

it('rejects public project submission when coauthor has no public contact consent', function (): void {
    Storage::fake('local');
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $category = Category::query()->create(['name' => 'Zieleń']);

    $this->actingAs(verifiedResident())
        ->from(route('public.projects.create'))
        ->post(route('public.projects.store'), [
            'budget_edition_id' => $edition->id,
            'project_area_id' => $area->id,
            'category_id' => $category->id,
            'local' => 1,
            'author_first_name' => 'Piotr',
            'author_last_name' => 'Kowalski',
            'author_email' => 'piotr@example.test',
            'author_email_agree' => '1',
            'author_read_confirm' => '1',
            'contact_with' => 1,
            'title' => 'Nowy park kieszonkowy',
            'localization' => 'Szczecin',
            'map_data' => json_encode(['type' => 'Point', 'coordinates' => [14.5528116, 53.4285432]]),
            'description' => 'Opis projektu',
            'goal' => 'Cel projektu',
            'argumentation' => 'Uzasadnienie',
            'availability' => 'Dostępność',
            'recipients' => 'Mieszkańcy',
            'free_of_charge' => 'Tak',
            'cost_items' => [
                ['description' => 'Zakup i montaż wyposażenia', 'amount' => 10000],
            ],
            'attachments_anonymized' => '1',
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
        ->assertSee('bo-spa-root')
        ->assertSee('Popraw tytu\\u0142 i kategori\\u0119.', false);

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

it('ignores invalid fields that are not whitelisted for public correction like legacy', function (): void {
    $author = User::factory()->create();
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $category = Category::query()->create(['name' => 'Zieleń']);
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'creator_id' => $author->id,
        'category_id' => $category->id,
        'status' => ProjectStatus::Submitted,
        'is_support_list' => true,
        'submitted_at' => now()->subDay(),
    ]));
    $project->costItems()->create([
        'description' => 'Prace projektowe',
        'amount' => 1000,
    ]);

    app(StartCorrectionAction::class)->execute(
        $project,
        $author,
        [ProjectCorrectionField::Title],
        'Popraw tylko tytuł.',
        now()->addDay(),
    );

    $this->actingAs($author)
        ->put(route('public.projects.corrections.update', $project), [
            'title' => 'Tytuł po korekcie',
            'goal' => ['tego pola nie wolno poprawić'],
            'category_id' => 'niepoprawna-kategoria',
            'attachment_files' => ['nie jest plikiem'],
        ])
        ->assertRedirect(route('public.projects.index'))
        ->assertSessionDoesntHaveErrors();

    $project->refresh();

    expect($project->title)->toBe('Tytuł po korekcie')
        ->and($project->goal)->toBe('Cel projektu')
        ->and($project->category_id)->toBe($category->id)
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
