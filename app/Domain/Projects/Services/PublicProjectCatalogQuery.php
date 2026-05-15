<?php

namespace App\Domain\Projects\Services;

use App\Domain\Projects\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class PublicProjectCatalogQuery
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        Log::info('public_project_catalog.query.start', [
            'budget_edition_id' => $filters['budget_edition_id'] ?? null,
            'area_id' => $filters['area_id'] ?? null,
            'category_id' => $filters['category_id'] ?? null,
            'has_query' => filled($filters['q'] ?? null),
        ]);

        $projects = Project::query()
            ->with(['area', 'budgetEdition', 'categories'])
            ->publiclyVisible()
            ->when($this->positiveInteger($filters, 'budget_edition_id'), function (Builder $query, int $editionId): void {
                $query->where('budget_edition_id', $editionId);
            })
            ->when($this->positiveInteger($filters, 'area_id'), function (Builder $query, int $areaId): void {
                $query->where('project_area_id', $areaId);
            })
            ->when($this->positiveInteger($filters, 'category_id'), function (Builder $query, int $categoryId): void {
                $query->where(function (Builder $query) use ($categoryId): void {
                    $query->where('category_id', $categoryId)
                        ->orWhereHas('categories', fn (Builder $query): Builder => $query->whereKey($categoryId));
                });
            })
            ->when($this->searchTerm($filters), function (Builder $query, string $term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('title', 'like', '%'.$term.'%')
                        ->orWhere('number', $term)
                        ->orWhere('number_drawn', $term);
                });
            })
            ->orderByRaw('number_drawn IS NULL')
            ->orderBy('number_drawn')
            ->orderByRaw('number IS NULL')
            ->orderBy('number')
            ->orderBy('title')
            ->paginate($perPage)
            ->withQueryString();

        Log::info('public_project_catalog.query.success', [
            'total' => $projects->total(),
            'current_page' => $projects->currentPage(),
        ]);

        return $projects;
    }

    private function positiveInteger(array $filters, string $key): ?int
    {
        $value = (int) ($filters[$key] ?? 0);

        return $value > 0 ? $value : null;
    }

    private function searchTerm(array $filters): ?string
    {
        $term = trim((string) ($filters['q'] ?? ''));

        return $term !== '' ? $term : null;
    }
}
