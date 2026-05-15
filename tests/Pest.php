<?php

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

function editionAttributes(): array
{
    return [
        'propose_start' => now()->subMonths(3),
        'propose_end' => now()->subMonths(2),
        'pre_voting_verification_end' => now()->subMonth(),
        'voting_start' => now()->subDay(),
        'voting_end' => now()->addDay(),
        'post_voting_verification_end' => now()->addWeek(),
        'result_announcement_end' => now()->addMonth(),
    ];
}

function budgetEdition(array $overrides = []): BudgetEdition
{
    return BudgetEdition::query()->create([
        ...editionAttributes(),
        ...$overrides,
    ]);
}

function areaAttributes(array $overrides = []): array
{
    return [
        'name' => 'Obszar lokalny',
        'symbol' => 'L1',
        'is_local' => true,
        ...$overrides,
    ];
}

function projectAttributes(int $editionId, int $areaId, array $overrides = []): array
{
    return [
        'budget_edition_id' => $editionId,
        'project_area_id' => $areaId,
        'title' => 'Nowy park kieszonkowy',
        'localization' => 'Szczecin',
        'description' => 'Opis projektu',
        'goal' => 'Cel projektu',
        'argumentation' => 'Uzasadnienie',
        'availability' => 'Dostępność',
        'recipients' => 'Mieszkańcy',
        'free_of_charge' => 'Tak',
        'status' => ProjectStatus::WorkingCopy,
        ...$overrides,
    ];
}

function project(int $editionId, int $areaId, array $overrides = []): Project
{
    return Project::query()->create(projectAttributes($editionId, $areaId, $overrides));
}
