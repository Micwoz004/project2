<?php

use App\Domain\Communications\Actions\AcceptProjectPublicCommentAction;
use App\Domain\Communications\Actions\AddProjectCommentAction;
use App\Domain\Communications\Actions\AddProjectPublicCommentAction;
use App\Domain\Communications\Actions\EditProjectPublicCommentAction;
use App\Domain\Communications\Actions\MarkCorrespondenceMessageReadAction;
use App\Domain\Communications\Actions\QueueProjectNotificationAction;
use App\Domain\Communications\Actions\SendProjectCorrespondenceMessageAction;
use App\Domain\Communications\Actions\ToggleProjectPublicCommentAdminHiddenAction;
use App\Domain\Communications\Actions\ToggleProjectPublicCommentHiddenAction;
use App\Domain\Communications\Enums\LegacyCommunicationTrigger;
use App\Domain\Communications\Enums\ProjectNotificationTemplate;
use App\Domain\Communications\Jobs\SendProjectNotificationJob;
use App\Domain\Communications\Models\MailLog;
use App\Domain\Communications\Models\ProjectNotification;
use App\Domain\Communications\Models\ProjectPublicComment;
use App\Domain\Communications\Services\ProjectPublicCommentVisibilityService;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Enums\SystemPermission;
use App\Domain\Users\Enums\SystemRole;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

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

it('allows applicants to add public comments and notifies the project creator', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();
    Bus::fake();

    $author = User::factory()->create();
    $applicant = User::factory()->create();
    $applicant->assignRole(SystemRole::Applicant->value);
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = project($edition->id, $area->id, [
        'creator_id' => $author->id,
    ]);

    $comment = app(AddProjectPublicCommentAction::class)->execute($project, $applicant, ' Widoczny komentarz ');

    expect($comment->project_id)->toBe($project->id)
        ->and($comment->created_by_id)->toBe($applicant->id)
        ->and($comment->content)->toBe('Widoczny komentarz')
        ->and($comment->moderated)->toBeTrue()
        ->and($project->publicComments()->count())->toBe(1)
        ->and(ProjectNotification::query()->where('project_id', $project->id)->where('sent_to_user_id', $author->id)->count())->toBe(1);

    Bus::assertDispatched(SendProjectNotificationJob::class);
});

it('rejects public comments from users without applicant role', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = project($edition->id, $area->id);
    $user = User::factory()->create();

    app(AddProjectPublicCommentAction::class)->execute($project, $user, 'Komentarz');
})->throws(DomainException::class, 'Brak uprawnień do dodania komentarza publicznego.');

it('lets public comment creators edit and hide their own comments', function (): void {
    $creator = User::factory()->create();
    $otherUser = User::factory()->create();
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = project($edition->id, $area->id);
    $comment = ProjectPublicComment::query()->create([
        'project_id' => $project->id,
        'created_by_id' => $creator->id,
        'content' => 'Pierwotnie',
        'moderated' => true,
    ]);

    $edited = app(EditProjectPublicCommentAction::class)->execute($comment, $creator, ' Po zmianie ');
    $hidden = app(ToggleProjectPublicCommentHiddenAction::class)->execute($edited, $creator);

    expect($edited->content)->toBe('Po zmianie')
        ->and($edited->moderated)->toBeTrue()
        ->and($hidden->hidden)->toBeTrue();

    app(EditProjectPublicCommentAction::class)->execute($hidden, $otherUser, 'Nie moje');
})->throws(DomainException::class, 'Można edytować tylko własny komentarz.');

it('lets admins accept and administratively hide public comments', function (): void {
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

    $accepted = app(AcceptProjectPublicCommentAction::class)->execute($comment, $admin);
    $hidden = app(ToggleProjectPublicCommentAdminHiddenAction::class)->execute($accepted, $admin);

    expect($accepted->moderated)->toBeTrue()
        ->and($hidden->admin_hidden)->toBeTrue()
        ->and(ProjectNotification::query()->where('project_id', $project->id)->where('sent_to_user_id', $creator->id)->count())->toBe(1);

    Bus::assertDispatched(SendProjectNotificationJob::class);
});

it('applies legacy public comment visibility rules', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $admin = User::factory()->create();
    $admin->assignRole(SystemRole::Admin->value);
    $author = User::factory()->create();
    $creator = User::factory()->create();
    $outsider = User::factory()->create();
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = project($edition->id, $area->id, [
        'creator_id' => $author->id,
    ]);
    $visibility = app(ProjectPublicCommentVisibilityService::class);

    $visible = ProjectPublicComment::query()->create([
        'project_id' => $project->id,
        'created_by_id' => $creator->id,
        'content' => 'Widoczny',
        'moderated' => true,
    ]);
    $pending = ProjectPublicComment::query()->create([
        'project_id' => $project->id,
        'created_by_id' => $creator->id,
        'content' => 'Oczekuje',
        'moderated' => false,
    ]);
    $hidden = ProjectPublicComment::query()->create([
        'project_id' => $project->id,
        'created_by_id' => $creator->id,
        'content' => 'Ukryty',
        'hidden' => true,
        'moderated' => true,
    ]);
    $adminHidden = ProjectPublicComment::query()->create([
        'project_id' => $project->id,
        'created_by_id' => $creator->id,
        'content' => 'Ukryty przez admina',
        'admin_hidden' => true,
        'moderated' => true,
    ]);

    expect($visibility->canView($visible, null))->toBeTrue()
        ->and($visibility->canView($pending, null))->toBeFalse()
        ->and($visibility->canView($pending, $creator))->toBeTrue()
        ->and($visibility->canView($pending, $admin))->toBeTrue()
        ->and($visibility->canView($pending, $author))->toBeTrue()
        ->and($visibility->canView($pending, $outsider))->toBeFalse()
        ->and($visibility->canView($hidden, $creator))->toBeTrue()
        ->and($visibility->canView($hidden, $admin))->toBeTrue()
        ->and($visibility->canView($hidden, null))->toBeFalse()
        ->and($visibility->canView($adminHidden, $admin))->toBeTrue()
        ->and($visibility->canView($adminHidden, $creator))->toBeFalse();
});

it('allows project author and managers to send and read correspondence', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();
    Bus::fake();

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
        ->and($project->correspondenceMessages()->count())->toBe(1)
        ->and(ProjectNotification::query()->where('project_id', $project->id)->where('sent_to_user_id', $manager->id)->count())->toBe(1);

    Bus::assertDispatched(SendProjectNotificationJob::class);
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

it('sends queued project notifications and writes mail logs', function (): void {
    Bus::fake();
    Mail::fake();

    $author = User::factory()->create();
    $recipient = User::factory()->create();
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = project($edition->id, $area->id, [
        'creator_id' => $author->id,
        'title' => 'Park kieszonkowy',
    ]);

    $notification = app(QueueProjectNotificationAction::class)->execute(
        $project,
        $author,
        $recipient,
        $recipient->email,
        ProjectNotificationTemplate::FormalCorrection,
        ['notes' => 'Uzupełnij kosztorys.'],
    );

    (new SendProjectNotificationJob($notification->id))->handle();

    expect($notification->subject)->toContain('Wezwanie do korekty projektu')
        ->and($notification->body)->toContain('Uzupełnij kosztorys.')
        ->and(MailLog::query()->where('email', $recipient->email)->where('subject', $notification->subject)->count())->toBe(1);
});

it('rejects queued project notifications without recipient email', function (): void {
    $author = User::factory()->create();
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = project($edition->id, $area->id, [
        'creator_id' => $author->id,
    ]);

    app(QueueProjectNotificationAction::class)->execute(
        $project,
        $author,
        null,
        '   ',
        ProjectNotificationTemplate::ProjectStatusChanged,
    );
})->throws(DomainException::class, 'Adres e-mail odbiorcy jest wymagany.');

it('keeps a legacy communication trigger map for mail and sms parity', function (): void {
    expect(LegacyCommunicationTrigger::cases())->toHaveCount(35)
        ->and(LegacyCommunicationTrigger::TaskStatusRejectedFormal->settingsKeys())->toBe([
            'subject' => 'emailTitleVerificationNegativeFormal',
            'body' => 'emailBodyVerificationNegativeFormal',
        ])
        ->and(LegacyCommunicationTrigger::VotingTokenSms->channel())->toBe('sms')
        ->and(LegacyCommunicationTrigger::VotingTokenSms->settingsKeys())->toBe([
            'body' => 'smsVotingToken',
        ])
        ->and(LegacyCommunicationTrigger::TaskCorrespondence->projectTemplate())->toBe(ProjectNotificationTemplate::CorrespondenceMessage)
        ->and(LegacyCommunicationTrigger::PublicCommentAdded->projectTemplate())->toBe(ProjectNotificationTemplate::PublicCommentAdded)
        ->and(LegacyCommunicationTrigger::PublicCommentAdminHidden->projectTemplate())->toBe(ProjectNotificationTemplate::PublicCommentAdminHidden)
        ->and(LegacyCommunicationTrigger::VerificationPressureAutomatic->projectTemplate())->toBe(ProjectNotificationTemplate::VerificationPressure);
});

it('documents the legacy source for every communication trigger', function (): void {
    foreach (LegacyCommunicationTrigger::cases() as $trigger) {
        expect($trigger->legacySource())->not->toBe('')
            ->and($trigger->recipient())->not->toBe('');
    }
});
