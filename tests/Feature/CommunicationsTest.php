<?php

use App\Domain\Communications\Actions\AddProjectCommentAction;
use App\Domain\Communications\Actions\MarkCorrespondenceMessageReadAction;
use App\Domain\Communications\Actions\SendProjectCorrespondenceMessageAction;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Models\User;

it('allows project managers to add internal comments', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = project($edition->id, $area->id);
    $manager = User::factory()->create();
    $manager->givePermissionTo(SystemPermission::ProjectsManage->value);

    $comment = app(AddProjectCommentAction::class)->execute($project, $manager, 'Notatka wewnętrzna');

    expect($comment->project_id)->toBe($project->id)
        ->and($comment->user_id)->toBe($manager->id)
        ->and($project->comments()->count())->toBe(1);
});

it('rejects project comments from unauthorized users', function (): void {
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = project($edition->id, $area->id);
    $user = User::factory()->create();

    app(AddProjectCommentAction::class)->execute($project, $user, 'Komentarz');
})->throws(DomainException::class, 'Brak uprawnień do dodania komentarza.');

it('allows project author and managers to send and read correspondence', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $author = User::factory()->create();
    $manager = User::factory()->create();
    $manager->givePermissionTo(SystemPermission::ProjectsManage->value);
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = project($edition->id, $area->id, [
        'creator_id' => $author->id,
    ]);

    $message = app(SendProjectCorrespondenceMessageAction::class)->execute(
        $project,
        $author,
        $manager,
        'Proszę o informację.',
    );
    $read = app(MarkCorrespondenceMessageReadAction::class)->execute($message, $manager);

    expect($message->project_id)->toBe($project->id)
        ->and($message->user_id)->toBe($manager->id)
        ->and($read->is_read)->toBeTrue()
        ->and($read->read_at)->not->toBeNull()
        ->and($project->correspondenceMessages()->count())->toBe(1);
});

it('rejects empty correspondence content', function (): void {
    $author = User::factory()->create();
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = project($edition->id, $area->id, [
        'creator_id' => $author->id,
    ]);

    app(SendProjectCorrespondenceMessageAction::class)->execute($project, $author, null, '   ');
})->throws(DomainException::class, 'Treść wiadomości nie może być pusta.');
