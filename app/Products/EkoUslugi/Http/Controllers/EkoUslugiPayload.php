<?php

namespace App\Products\EkoUslugi\Http\Controllers;

use App\Products\EkoUslugi\Domain\Address\Models\ResidentAddress;
use App\Products\EkoUslugi\Domain\AirQuality\Models\AirQualityStation;
use App\Products\EkoUslugi\Domain\News\Models\NewsPost;
use App\Products\EkoUslugi\Domain\Pszok\Models\PszokPoint;
use App\Products\EkoUslugi\Domain\Schedule\Models\CollectionScheduleDate;
use App\Products\EkoUslugi\Domain\Waste\Models\WasteFraction;
use App\Products\EkoUslugi\Domain\Waste\Models\WasteItem;

class EkoUslugiPayload
{
    /**
     * @return array<string, mixed>
     */
    public function address(ResidentAddress $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'buildingType' => $address->building_type,
            'active' => $address->is_active,
            'confirmationStatus' => $address->confirmation_status,
            'locality' => $address->locality,
            'street' => $address->street,
            'buildingNumber' => $address->building_number,
            'apartmentNumber' => $address->apartment_number,
            'postalCode' => $address->postal_code,
            'zone' => $address->zone ? [
                'id' => $address->zone->id,
                'name' => $address->zone->name,
                'code' => $address->zone->code,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fraction(WasteFraction $fraction): array
    {
        return [
            'id' => $fraction->id,
            'name' => $fraction->name,
            'color' => $fraction->color,
            'icon' => $fraction->icon,
            'description' => $fraction->description,
            'whatToPut' => $fraction->what_to_put,
            'whatNotToPut' => $fraction->what_not_to_put,
            'status' => $fraction->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function wasteItem(WasteItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'instruction' => $item->instruction,
            'goesToPszok' => $item->goes_to_pszok,
            'status' => $item->status,
            'fraction' => $item->fraction ? $this->fraction($item->fraction) : null,
            'synonyms' => $item->synonyms->pluck('synonym')->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pszok(PszokPoint $point): array
    {
        return [
            'id' => $point->id,
            'name' => $point->name,
            'status' => $point->status,
            'phone' => $point->phone,
            'email' => $point->email,
            'description' => $point->description,
            'address' => [
                'locality' => $point->locality,
                'street' => $point->street,
                'buildingNumber' => $point->building_number,
                'postalCode' => $point->postal_code,
            ],
            'openingHours' => $point->opening_hours ?? [],
            'fractions' => $point->fractions->map(fn (WasteFraction $fraction): array => $this->fraction($fraction))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function scheduleDate(CollectionScheduleDate $date): array
    {
        return [
            'id' => $date->id,
            'collectionDate' => $date->collection_date?->format('Y-m-d'),
            'fraction' => $date->fraction ? $this->fraction($date->fraction) : null,
            'zone' => $date->zone ? [
                'id' => $date->zone->id,
                'name' => $date->zone->name,
            ] : null,
            'schedule' => $date->schedule ? [
                'id' => $date->schedule->id,
                'name' => $date->schedule->name,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function newsPost(NewsPost $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'lead' => $post->lead,
            'body' => $post->body,
            'publishedAt' => $post->published_at?->format('Y-m-d H:i'),
            'url' => route('eko-uslugi.news.show', $post->slug),
            'category' => $post->category?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function airStation(AirQualityStation $station): array
    {
        $latest = $station->latestReadings->first();

        return [
            'id' => $station->id,
            'name' => $station->name,
            'city' => $station->city,
            'street' => $station->street,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'indexValue' => $latest?->index_value,
            'indexCategoryName' => $latest?->index_category_name,
            'measuredAt' => $latest?->measured_at?->format('Y-m-d H:i'),
            'readings' => $station->latestReadings->map(fn ($reading): array => [
                'parameterCode' => $reading->parameter_code,
                'parameterName' => $reading->parameter_name,
                'value' => $reading->value,
                'unit' => $reading->unit,
                'measuredAt' => $reading->measured_at?->format('Y-m-d H:i'),
            ])->values()->all(),
        ];
    }
}
