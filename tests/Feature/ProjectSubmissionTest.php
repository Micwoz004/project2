<?php

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Files\Models\ProjectFile;
use App\Domain\Projects\Actions\SubmitProjectAction;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Models\User;

it('submits a project and records a version snapshot', function (): void {
    $user = User::factory()->create();
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id));

    $project->costItems()->create([
        'description' => 'Zakup i montaż wyposażenia',
        'amount' => 10000,
    ]);

    ProjectFile::query()->create([
        'project_id' => $project->id,
        'stored_name' => 'support.pdf',
        'original_name' => 'support.pdf',
        'type' => ProjectFileType::SupportList,
    ]);

    $submitted = app(SubmitProjectAction::class)->execute($project, $user);

    expect($submitted->status)->toBe(ProjectStatus::Submitted)
        ->and($submitted->versions()->count())->toBe(1);
});

it('rejects project text with urls like legacy propose validation', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create([
        ...projectAttributes($edition->id, $area->id),
        'description' => 'Opis z https://example.test',
        'is_support_list' => true,
    ]);

    $project->costItems()->create([
        'description' => 'Prace',
        'amount' => 1000,
    ]);

    app(SubmitProjectAction::class)->execute($project);
})->throws(DomainException::class, 'Pola opisowe projektu nie mogą zawierać adresów URL.');
