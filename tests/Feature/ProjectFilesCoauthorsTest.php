<?php

use App\Domain\Files\Actions\RegisterProjectFileAction;
use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Projects\Actions\SyncProjectCoauthorsAction;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Models\User;

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
