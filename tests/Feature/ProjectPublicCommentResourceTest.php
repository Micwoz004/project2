<?php

use App\Domain\Communications\Jobs\SendProjectNotificationJob;
use App\Domain\Communications\Models\ProjectNotification;
use App\Domain\Communications\Models\ProjectPublicComment;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemRole;
use App\Filament\Resources\ProjectPublicComments\ProjectPublicCommentResource;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

it('registers public comment moderation resource as list only', function (): void {
    expect(ProjectPublicCommentResource::canCreate())->toBeFalse()
        ->and(array_keys(ProjectPublicCommentResource::getPages()))->toBe(['index']);
});

it('allows only admins to access public comment moderation resource', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $admin = User::factory()->create();
    $admin->assignRole(SystemRole::Admin->value);
    $bdo = User::factory()->create();
    $bdo->assignRole(SystemRole::Bdo->value);
    $applicant = User::factory()->create();
    $applicant->assignRole(SystemRole::Applicant->value);

    $this->actingAs($admin);
    expect(ProjectPublicCommentResource::canViewAny())->toBeTrue();

    $this->actingAs($bdo);
    expect(ProjectPublicCommentResource::canViewAny())->toBeFalse();

    $this->actingAs($applicant);
    expect(ProjectPublicCommentResource::canViewAny())->toBeFalse();
});

it('accepts and administratively hides public comments through filament resource actions', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();
    Bus::fake();

    $admin = User::factory()->create();
    $admin->assignRole(SystemRole::Admin->value);
    $creator = User::factory()->create();
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = project($edition->id, $area->id);
    $comment = ProjectPublicComment::query()->create([
        'project_id' => $project->id,
        'created_by_id' => $creator->id,
        'content' => 'Do moderacji',
        'moderated' => false,
    ]);

    $this->actingAs($admin);

    $accepted = ProjectPublicCommentResource::acceptFromAdmin($comment);
    $hidden = ProjectPublicCommentResource::toggleAdminHiddenFromAdmin($accepted);

    expect($accepted->moderated)->toBeTrue()
        ->and($hidden->admin_hidden)->toBeTrue()
        ->and(ProjectNotification::query()->where('project_id', $project->id)->where('sent_to_user_id', $creator->id)->count())->toBe(1);

    Bus::assertDispatched(SendProjectNotificationJob::class);
});

it('rejects public comment moderation through filament for non admins', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $applicant = User::factory()->create();
    $applicant->assignRole(SystemRole::Applicant->value);
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = project($edition->id, $area->id);
    $comment = ProjectPublicComment::query()->create([
        'project_id' => $project->id,
        'created_by_id' => $applicant->id,
        'content' => 'Do moderacji',
        'moderated' => false,
    ]);

    $this->actingAs($applicant);

    ProjectPublicCommentResource::acceptFromAdmin($comment);
})->throws(DomainException::class, 'Tylko administrator może zaakceptować komentarz.');
