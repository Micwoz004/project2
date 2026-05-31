<?php

use App\Products\CivicBudget\Domain\Projects\Enums\ProjectCorrectionField;
use App\Products\CivicBudget\Domain\Projects\Enums\ProjectStatus;
use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Products\CivicBudget\Domain\Projects\Models\ProjectArea;
use App\Platform\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Platform\Users\Enums\SystemPermission;
use App\Platform\Users\Enums\SystemRole;
use App\Platform\Users\Models\Department;
use App\Products\CivicBudget\Domain\Verification\Enums\VerificationAssignmentType;
use App\Products\CivicBudget\Domain\Verification\Models\FormalVerification;
use App\Products\CivicBudget\Domain\Verification\Models\VerificationVersion;
use App\Products\CivicBudget\Filament\Resources\Projects\ProjectResource;
use App\Models\User;

function formalResourceProject(ProjectStatus $status, array $overrides = []): Project
{
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());

    return project($edition->id, $area->id, [
        'status' => $status,
        'is_support_list' => true,
        ...$overrides,
    ]);
}

it('shows formal verification actions only to project verifiers for matching statuses', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $submitted = formalResourceProject(ProjectStatus::Submitted);
    $duringFormal = formalResourceProject(ProjectStatus::DuringFormalVerification);
    $formallyVerified = formalResourceProject(ProjectStatus::FormallyVerified);
    $picked = formalResourceProject(ProjectStatus::Picked);

    expect(ProjectResource::canBeginFormalVerification($submitted))->toBeTrue()
        ->and(ProjectResource::canBeginFormalVerification($duringFormal))->toBeFalse()
        ->and(ProjectResource::canCompleteFormalVerification($submitted))->toBeTrue()
        ->and(ProjectResource::canCompleteFormalVerification($duringFormal))->toBeTrue()
        ->and(ProjectResource::canRequestFormalCorrection($duringFormal))->toBeTrue()
        ->and(ProjectResource::canForwardFormalVerification($formallyVerified))->toBeTrue()
        ->and(ProjectResource::canForwardFormalVerification($picked))->toBeFalse();

    $applicant = User::factory()->create();
    $applicant->assignRole(SystemRole::Applicant->value);
    $this->actingAs($applicant);

    expect(ProjectResource::canBeginFormalVerification($submitted))->toBeFalse()
        ->and(ProjectResource::canCompleteFormalVerification($duringFormal))->toBeFalse()
        ->and(ProjectResource::canRequestFormalCorrection($duringFormal))->toBeFalse()
        ->and(ProjectResource::canForwardFormalVerification($formallyVerified))->toBeFalse();
});

it('registers extended project list and detail pages for administrators', function (): void {
    expect(array_keys(ProjectResource::getPages()))->toBe(['index', 'create', 'view', 'edit']);
});

it('renders extended project list with operational filters', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole(SystemRole::Admin->value);
    formalResourceProject(ProjectStatus::Submitted, [
        'title' => 'Projekt do filtrowania',
        'number_drawn' => 12,
    ]);

    $this->actingAs($admin)
        ->get(ProjectResource::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('Projekt do filtrowania')
        ->assertSee('zgłoszony do Urzędu')
        ->assertSee('Podgląd');
});

it('renders project detail card with project and verification information', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole(SystemRole::Admin->value);
    $project = formalResourceProject(ProjectStatus::FormallyVerified, [
        'title' => 'Projekt z kartą zbiorczą',
        'number_drawn' => 2,
        'authors' => [
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email' => 'anna@example.test',
        ],
    ]);

    FormalVerification::query()->create([
        'project_id' => $project->id,
        'created_by_id' => $admin->id,
        'status' => ProjectStatus::FormallyVerified->value,
        'result' => true,
        'answers' => [
            'wasSentOnCorrectForm' => 1,
            'hasSupportAttachment' => 1,
        ],
    ]);

    $this->actingAs($admin)
        ->get(ProjectResource::getUrl('view', ['record' => $project], panel: 'admin'))
        ->assertOk()
        ->assertSee('Projekt z kartą zbiorczą')
        ->assertSee('Weryfikacja formalna')
        ->assertSee('Anna Nowak')
        ->assertSee('wynik: pozytywny');
});

it('allows formal verification actions through granular permission', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $formalVerifier = User::factory()->create();
    $formalVerifier->givePermissionTo(SystemPermission::FormalVerificationManage->value);
    $this->actingAs($formalVerifier);

    $submitted = formalResourceProject(ProjectStatus::Submitted);

    expect(ProjectResource::canBeginFormalVerification($submitted))->toBeTrue();

    $meritOnly = User::factory()->create();
    $meritOnly->givePermissionTo(SystemPermission::MeritVerificationManage->value);
    $this->actingAs($meritOnly);

    expect(ProjectResource::canBeginFormalVerification($submitted))->toBeFalse();
});

it('uses legacy formal verification question labels', function (): void {
    $labels = ProjectResource::formalVerificationFieldLabels();

    expect($labels['has_leader_contact_data'])
        ->toBe('Czy projekt zawiera dane kontaktowe do autora  i współautorów (imię i nazwisko, numer telefonu, adres e-mail oraz miejsce zamieszkania)?')
        ->and($labels['has_support_attachment'])
        ->toBe('Czy załączona została lista poparcia zawierająca podpisy minimum 10 osób popierających projekt, z wyłączeniem autora projektu?')
        ->and($labels['is_in_budget'])
        ->toBe('Czy wartość projektu określona przez autora mieści się w puli środków SBO przeznaczonych na projekty z danej kategorii i danego obszaru lokalnego?');
});

it('completes positive formal verification from filament form through domain action', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $project = formalResourceProject(ProjectStatus::DuringFormalVerification);

    $verification = ProjectResource::completeFormalVerificationFromAdminForm($project, [
        'was_sent_on_correct_form' => true,
        'has_support_attachment' => true,
        'is_map_correct' => false,
        'is_map_correct_comments' => 'Brak numeru działki.',
        'is_project_category' => 1,
    ], true);

    expect($verification->result)->toBeTrue()
        ->and($verification->answers)->toMatchArray([
            'wasSentOnCorrectForm' => 1,
            'hasSupportAttachment' => 1,
            'isMapCorrect' => 0,
            'isMapCorrectComments' => 'Brak numeru działki.',
            'isProjectCategory' => 1,
        ])
        ->and($verification->answers['wasSentInTime'])->toBe(0)
        ->and($project->refresh()->status)->toBe(ProjectStatus::FormallyVerified)
        ->and(VerificationVersion::query()->where('verification_legacy_id', $verification->id)->count())->toBe(1);
});

it('requests formal correction from filament form through domain action', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $project = formalResourceProject(ProjectStatus::DuringFormalVerification);

    $correction = ProjectResource::requestFormalCorrectionFromAdminForm($project, [
        'allowed_fields' => [
            ProjectCorrectionField::Description->value,
            ProjectCorrectionField::SupportAttachment->value,
        ],
        'notes' => 'Uzupełnić opis i listę poparcia.',
    ]);

    expect($correction->allowed_fields)->toBe([
        ProjectCorrectionField::Description->value,
        ProjectCorrectionField::SupportAttachment->value,
    ])
        ->and($project->refresh()->status)->toBe(ProjectStatus::DuringFormalVerification)
        ->and($project->need_correction)->toBeTrue();
});

it('forwards formally verified project from filament form to initial merit departments', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $coordinator = User::factory()->create();
    $coordinator->assignRole(SystemRole::Coordinator->value);
    $this->actingAs($coordinator);

    $project = formalResourceProject(ProjectStatus::FormallyVerified);
    $firstDepartment = Department::query()->create(['name' => 'Wydział Inwestycji']);
    $secondDepartment = Department::query()->create(['name' => 'Wydział Zieleni']);

    $updated = ProjectResource::forwardFormalVerificationFromAdminForm($project, [
        'department_ids' => [$firstDepartment->id, $secondDepartment->id],
        'notes' => 'Do opinii wstępnej.',
    ]);

    expect($updated->status)->toBe(ProjectStatus::DuringInitialVerification)
        ->and($updated->need_pre_verification)->toBeTrue()
        ->and($project->verificationAssignments()
            ->where('type', VerificationAssignmentType::MeritInitial->value)
            ->count())->toBe(2);
});
