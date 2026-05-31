<?php

namespace App\Products\EcoUslugi\Domain\Schedule\Services;

use App\Products\EcoUslugi\Domain\Address\Models\ResidentAddress;
use App\Products\EcoUslugi\Domain\Schedule\Models\CollectionScheduleDate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class UpcomingCollectionService
{
    /**
     * @return Collection<int, CollectionScheduleDate>
     */
    public function forAddress(ResidentAddress $address, int $limit = 20): Collection
    {
        Log::info('eco_uslugi.schedule.upcoming.start', [
            'resident_address_id' => $address->id,
            'eco_zone_id' => $address->eco_zone_id,
        ]);

        if ($address->eco_zone_id === null) {
            Log::warning('eco_uslugi.schedule.upcoming.rejected_missing_zone', [
                'resident_address_id' => $address->id,
            ]);

            return CollectionScheduleDate::newCollection();
        }

        $dates = CollectionScheduleDate::query()
            ->with(['fraction', 'zone', 'schedule'])
            ->where('eco_zone_id', $address->eco_zone_id)
            ->whereDate('collection_date', '>=', now()->toDateString())
            ->whereHas('schedule', fn ($query) => $query->active())
            ->orderBy('collection_date')
            ->limit($limit)
            ->get();

        Log::info('eco_uslugi.schedule.upcoming.success', [
            'resident_address_id' => $address->id,
            'dates_count' => $dates->count(),
        ]);

        return $dates;
    }
}
