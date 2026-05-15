<?php

use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

it('hides non-public projects from guests and allows project managers to see them', function (): void {
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Submitted,
    ]));

    expect(Gate::allows('view', $project))->toBeFalse();

    $admin = User::factory()->create();
    Permission::findOrCreate('projects.manage');
    $admin->givePermissionTo('projects.manage');

    expect(Gate::forUser($admin)->allows('view', $project))->toBeTrue();
});

it('allows applicant edit only for drafts or active corrections', function (): void {
    $user = User::factory()->create();
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $draft = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'creator_id' => $user->id,
    ]));
    $submitted = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'creator_id' => $user->id,
        'title' => 'Projekt złożony',
        'status' => ProjectStatus::Submitted,
    ]));
    $correction = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'creator_id' => $user->id,
        'title' => 'Projekt w korekcie',
        'status' => ProjectStatus::Submitted,
        'need_correction' => true,
        'correction_start_time' => now()->subDay(),
        'correction_end_time' => now()->addDay(),
    ]));

    expect(Gate::forUser($user)->allows('update', $draft))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $submitted))->toBeFalse()
        ->and(Gate::forUser($user)->allows('update', $correction))->toBeTrue();
});
