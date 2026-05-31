<?php

use App\Products\CivicBudget\Domain\Files\Enums\ProjectFileType;
use App\Products\CivicBudget\Domain\Projects\Enums\ProjectStatus;
use App\Products\CivicBudget\Domain\Projects\Models\Category;
use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Products\CivicBudget\Domain\Projects\Models\ProjectArea;
use App\Products\CivicBudget\Domain\Voting\Enums\CitizenConfirmation;
use App\Products\CivicBudget\Domain\Voting\Enums\VoteCardStatus;
use App\Products\CivicBudget\Domain\Voting\Models\VoteCard;
use App\Products\CivicBudget\Domain\Voting\Models\VotingToken;
use App\Models\User;
use App\Notifications\ResidentEmailVerification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('authenticates a resident with a mobile bearer token', function (): void {
    $resident = User::factory()->create([
        'status' => true,
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
        'password' => 'secret-password',
    ]);

    $login = $this->postJson('/api/mobile/resident/login', [
        'email' => $resident->email,
        'password' => 'secret-password',
    ])->assertOk();

    $token = $login->json('accessToken');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/mobile/resident/me')
        ->assertOk()
        ->assertJsonPath('user.email', $resident->email)
        ->assertJsonPath('user.firstName', 'Jan');
});

it('registers updates and lists resident account data through the mobile API', function (): void {
    Notification::fake();

    $register = $this->postJson('/api/mobile/resident/register', [
        'firstName' => 'Anna',
        'lastName' => 'Nowak',
        'email' => 'anna.nowak@example.test',
        'phone' => '500600701',
        'password' => 'Strong-pass-123!',
        'password_confirmation' => 'Strong-pass-123!',
    ])->assertCreated()
        ->assertJsonPath('user.email', 'anna.nowak@example.test')
        ->assertJsonPath('user.firstName', 'Anna');

    $token = $register->json('accessToken');
    $user = User::query()->where('email', 'anna.nowak@example.test')->firstOrFail();

    Notification::assertSentTo($user, ResidentEmailVerification::class);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/mobile/resident/account', [
            'first_name' => 'Anna',
            'last_name' => 'Kowalska',
            'email' => 'anna.kowalska@example.test',
            'phone' => '500600702',
            'street' => 'Jasna',
            'house_no' => '12',
            'flat_no' => '3',
            'post_code' => '70-001',
            'city' => 'Szczecin',
        ])
        ->assertOk()
        ->assertJsonPath('user.email', 'anna.kowalska@example.test')
        ->assertJsonPath('user.lastName', 'Kowalska')
        ->assertJsonPath('message', 'Dane konta zostały zapisane.');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/mobile/resident/projects')
        ->assertOk()
        ->assertJsonPath('items', []);
});

it('serves civic budget overview for the mobile app', function (): void {
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $category = Category::query()->create(['name' => 'Zieleń']);
    $project = project($edition->id, $area->id, [
        'category_id' => $category->id,
        'status' => ProjectStatus::Picked,
        'submitted_at' => now(),
        'cost_formatted' => 15000,
    ]);
    $project->categories()->sync([$category->id]);

    $this->getJson('/api/mobile/civic-budget/overview')
        ->assertOk()
        ->assertJsonPath('activeEdition.id', (string) $edition->id)
        ->assertJsonPath('featuredProjects.0.id', (string) $project->id)
        ->assertJsonPath('featuredProjects.0.canVote', true);
});

it('stores a submitted resident project through the mobile API', function (): void {
    Storage::fake('local');
    Storage::fake('public');

    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $category = Category::query()->create(['name' => 'Zieleń']);
    $resident = User::factory()->create([
        'status' => true,
        'first_name' => 'Piotr',
        'last_name' => 'Kowalski',
        'phone' => '500600700',
    ]);
    $token = $this->postJson('/api/mobile/resident/login', [
        'email' => $resident->email,
        'password' => 'password',
    ])->assertOk()->json('accessToken');

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ])->post('/api/mobile/resident/projects', [
        '_intent' => 'submit',
        'budget_edition_id' => $edition->id,
        'project_area_id' => $area->id,
        'category_id' => $category->id,
        'local' => 1,
        'author_first_name' => 'Piotr',
        'author_last_name' => 'Kowalski',
        'author_email' => $resident->email,
        'author_phone' => '500600700',
        'author_email_agree' => '1',
        'author_personal_data_agree' => '1',
        'author_read_confirm' => '1',
        'contact_with' => 1,
        'title' => 'Mobilny projekt mieszkańca',
        'short_description' => 'Krótki opis projektu mobilnego.',
        'localization' => 'Szczecin',
        'map_data' => json_encode(['type' => 'mobile']),
        'description' => 'Opis projektu z aplikacji mobilnej',
        'goal' => 'Cel projektu',
        'argumentation' => 'Uzasadnienie projektu',
        'availability' => 'Dostępność dla mieszkańców',
        'recipients' => 'Mieszkańcy',
        'free_of_charge' => 'Tak',
        'cost_items' => [
            ['description' => 'Zakup i montaż', 'amount' => 10000],
        ],
        'consent_to_change' => '1',
        'show_task_coauthors' => '1',
        'attachments_anonymized' => '1',
        'support_list' => '1',
        'support_list_file' => UploadedFile::fake()->create('lista-poparcia.pdf', 128, 'application/pdf'),
    ])->assertCreated()
        ->assertJsonPath('project.name', 'Mobilny projekt mieszkańca')
        ->assertJsonPath('project.status', 'submitted');

    $project = Project::query()->firstOrFail();
    $supportListFile = $project->files()->where('type', ProjectFileType::SupportList)->firstOrFail();

    Storage::disk('local')->assertExists($supportListFile->stored_name);

    expect($project->status)->toBe(ProjectStatus::Submitted)
        ->and($project->creator_id)->toBe($resident->id)
        ->and($project->cost_formatted)->toBe('10000.00');
});

it('casts a vote through the mobile voting API', function (): void {
    $edition = budgetEdition();
    $localArea = ProjectArea::query()->create(areaAttributes());
    $citywideArea = ProjectArea::query()->create(areaAttributes([
        'name' => 'Ogólnomiejskie',
        'symbol' => 'OGM',
        'is_local' => false,
    ]));
    $localProject = project($edition->id, $localArea->id, [
        'status' => ProjectStatus::Picked,
        'number_drawn' => 1,
    ]);
    $citywideProject = project($edition->id, $citywideArea->id, [
        'title' => 'Projekt ogólnomiejski',
        'status' => ProjectStatus::Picked,
        'number_drawn' => 2,
    ]);

    $this->getJson("/api/mobile/civic-budget/editions/{$edition->id}/voting")
        ->assertOk()
        ->assertJsonPath('votingOpen', true)
        ->assertJsonPath('localProjects.0.id', (string) $localProject->id)
        ->assertJsonPath('citywideProjects.0.id', (string) $citywideProject->id);

    $voter = mobileVoterPayload();

    $this->postJson('/api/mobile/civic-budget/voting/token', $voter)
        ->assertOk()
        ->assertJsonPath('canProceed', true);

    $token = VotingToken::query()->firstOrFail();

    $this->postJson('/api/mobile/civic-budget/voting/cast', [
        ...$voter,
        'budget_edition_id' => $edition->id,
        'sms_token' => $token->token,
        'local_project_id' => $localProject->id,
        'citywide_project_id' => $citywideProject->id,
        'citizen_confirm' => CitizenConfirmation::Living->value,
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Głos został zapisany.');

    $voteCard = VoteCard::query()->firstOrFail();

    expect($voteCard->status)->toBe(VoteCardStatus::Accepted)
        ->and($voteCard->votes()->pluck('project_id')->sort()->values()->all())
        ->toBe(collect([$localProject->id, $citywideProject->id])->sort()->values()->toArray())
        ->and($token->refresh()->disabled)->toBeTrue();
});

function mobileVoterPayload(): array
{
    return [
        'pesel' => '44051401458',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
        'mother_last_name' => 'Nowak',
        'phone' => '500600700',
    ];
}
