<?php

namespace App\Domain\Projects\Services;

use App\Domain\Projects\Models\ProjectArea;
use Illuminate\Support\Collection;

class ProjectAreaCatalog
{
    public function localAreas(bool $orderByName = true): Collection
    {
        return ProjectArea::query()
            ->where('is_local', true)
            ->when($orderByName, fn ($query) => $query->orderBy('name'), fn ($query) => $query->orderBy('id'))
            ->get();
    }

    public function allForVoting(): Collection
    {
        return ProjectArea::query()
            ->orderBy('is_local')
            ->orderBy('name')
            ->get();
    }

    public function citywideArea(): ?ProjectArea
    {
        return ProjectArea::query()
            ->where('is_local', false)
            ->orWhere('symbol', 'OGM')
            ->orderBy('id')
            ->first();
    }
}
