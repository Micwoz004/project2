<?php

use App\Domain\Files\Actions\RegisterProjectFileAction;
use App\Domain\Files\Actions\StoreProjectFileAction;
use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Projects\Actions\SyncProjectCoauthorsAction;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function draftProjectWithAuthor(User $user): Project
{
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());

    return project($edition->id, $area->id, [
        'creator_id' => $user->id,
    ]);
}

it('registers project files with legacy extension and count limits', function (): void {
    $user = User::factory()->create();
    $project = draftProjectWithAuthor($user);

    $file = app(RegisterProjectFileAction::class)->execute(
        $project,
        ProjectFileType::SupportList,
        'support-1.pdf',
        'lista-poparcia.pdf',
        1024,
        $user,
    );

    expect($file->type)->toBe(ProjectFileType::SupportList)
        ->and($file->created_by_id)->toBe($user->id);

    foreach (range(2, 5) as $number) {
        app(RegisterProjectFileAction::class)->execute(
            $project,
            ProjectFileType::SupportList,
            "support-$number.pdf",
            "lista-poparcia-$number.pdf",
            1024,
            $user,
        );
    }

    app(RegisterProjectFileAction::class)->execute(
        $project,
        ProjectFileType::SupportList,
        'support-6.pdf',
        'lista-poparcia-6.pdf',
        1024,
        $user,
    );
})->throws(DomainException::class, 'Przekroczono maksymalną liczbę załączników danego typu.');

it('rejects project files with extensions outside legacy allow-list', function (): void {
    $user = User::factory()->create();
    $project = draftProjectWithAuthor($user);

    app(RegisterProjectFileAction::class)->execute(
        $project,
        ProjectFileType::Other,
        'script.php',
        'script.php',
        1024,
        $user,
    );
})->throws(DomainException::class, 'Niedozwolony typ pliku załącznika.');

it('stores public and private project files on matching disks', function (): void {
    Storage::fake('public');
    Storage::fake('local');
    $user = User::factory()->create();
    $project = draftProjectWithAuthor($user);

    $publicFile = app(StoreProjectFileAction::class)->execute(
        $project,
        ProjectFileType::Other,
        UploadedFile::fake()->create('zalacznik.pdf', 128, 'application/pdf'),
        $user,
        'Opis publiczny',
    );
    $privateFile = app(StoreProjectFileAction::class)->execute(
        $project,
        ProjectFileType::OwnerAgreement,
        UploadedFile::fake()->create('zgoda.pdf', 128, 'application/pdf'),
        $user,
        'Opis prywatny',
        true,
    );

    Storage::disk('public')->assertExists($publicFile->stored_name);
    Storage::disk('local')->assertExists($privateFile->stored_name);

    expect($publicFile->is_private)->toBeFalse()
        ->and($privateFile->is_private)->toBeTrue()
        ->and($publicFile->original_name)->toBe('zalacznik.pdf')
        ->and($privateFile->original_name)->toBe('zgoda.pdf');
});

it('does not store project file when legacy validation rejects upload', function (): void {
    Storage::fake('public');
    $user = User::factory()->create();
    $project = draftProjectWithAuthor($user);

    app(StoreProjectFileAction::class)->execute(
        $project,
        ProjectFileType::Other,
        UploadedFile::fake()->create('skrypt.php', 128, 'application/x-php'),
        $user,
    );
})->throws(DomainException::class, 'Niedozwolony typ pliku załącznika.');

it('syncs up to two coauthors with required contact and confirmations', function (): void {
    $user = User::factory()->create();
    $project = draftProjectWithAuthor($user);

    app(SyncProjectCoauthorsAction::class)->execute($project, [
        [
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email' => 'anna@example.test',
            'read_confirm' => true,
            'email_agree' => true,
        ],
        [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'phone' => '500600700',
            'read_confirm' => true,
            'phone_agree' => true,
        ],
    ]);

    expect($project->coauthors()->count())->toBe(2);
});

it('rejects coauthors without contact consent like legacy Cocreator validateContact', function (): void {
    $user = User::factory()->create();
    $project = draftProjectWithAuthor($user);

    app(SyncProjectCoauthorsAction::class)->execute($project, [
        [
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email' => 'anna@example.test',
            'read_confirm' => true,
            'email_agree' => false,
            'phone_agree' => false,
        ],
    ]);
})->throws(DomainException::class, 'Współautor musi wybrać co najmniej jedną formę kontaktu.');
