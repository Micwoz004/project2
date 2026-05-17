<?php

namespace App\Domain\Projects\Services;

use App\Domain\Projects\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PublicProjectMapQuery
{
    public function __construct(
        private readonly PublicProjectCatalogQuery $catalogQuery,
    ) {}

    /**
     * @return Collection<int, array{project: Project, lat: float, lng: float, geometry: array<mixed>}>
     */
    public function get(array $filters): Collection
    {
        Log::info('public_project_map.query.start', [
            'budget_edition_id' => $filters['budget_edition_id'] ?? null,
            'area_id' => $filters['area_id'] ?? null,
            'category_id' => $filters['category_id'] ?? null,
            'has_query' => filled($filters['q'] ?? null),
        ]);

        $projects = $this->catalogQuery
            ->query($filters)
            ->orderByRaw('number_drawn IS NULL')
            ->orderBy('number_drawn')
            ->orderBy('title')
            ->get()
            ->map(fn (Project $project): ?array => $this->mapProject($project))
            ->filter()
            ->values();

        Log::info('public_project_map.query.success', [
            'projects_count' => $projects->count(),
        ]);

        return $projects;
    }

    /**
     * @return array{project: Project, lat: float, lng: float, geometry: array<mixed>}|null
     */
    private function mapProject(Project $project): ?array
    {
        $coordinates = $this->coordinates($project);

        if ($coordinates === null) {
            Log::info('public_project_map.project_skipped_missing_coordinates', [
                'project_id' => $project->id,
            ]);

            return null;
        }

        return [
            'project' => $project,
            'lat' => $coordinates['lat'],
            'lng' => $coordinates['lng'],
            'geometry' => $project->map_data ?? [],
        ];
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    private function coordinates(Project $project): ?array
    {
        if ($project->lat !== null && $project->lng !== null) {
            return [
                'lat' => (float) $project->lat,
                'lng' => (float) $project->lng,
            ];
        }

        $fromMapData = $this->coordinatesFromMapData($project->map_data ?? []);

        if ($fromMapData !== null) {
            return $fromMapData;
        }

        return $this->coordinatesFromMapLngLat((string) $project->map_lng_lat);
    }

    /**
     * @param  array<mixed>  $mapData
     * @return array{lat: float, lng: float}|null
     */
    private function coordinatesFromMapData(array $mapData): ?array
    {
        foreach ($mapData as $shape) {
            if (! is_array($shape)) {
                continue;
            }

            $coords = $shape['coords'] ?? null;

            if (is_array($coords) && isset($coords['lat'], $coords['lng'])) {
                return [
                    'lat' => (float) $coords['lat'],
                    'lng' => (float) $coords['lng'],
                ];
            }

            if (is_array($coords)) {
                foreach ($coords as $point) {
                    if (is_array($point) && isset($point['lat'], $point['lng'])) {
                        return [
                            'lat' => (float) $point['lat'],
                            'lng' => (float) $point['lng'],
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    private function coordinatesFromMapLngLat(string $value): ?array
    {
        $parts = collect(explode(',', $value))
            ->map(fn (string $part): string => trim($part))
            ->filter(fn (string $part): bool => is_numeric($part))
            ->values();

        if ($parts->count() !== 2) {
            return null;
        }

        $first = (float) $parts[0];
        $second = (float) $parts[1];

        if (abs($first) <= 30 && abs($second) > 30) {
            return [
                'lat' => $second,
                'lng' => $first,
            ];
        }

        return [
            'lat' => $first,
            'lng' => $second,
        ];
    }
}
