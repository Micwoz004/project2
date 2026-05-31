<?php

namespace App\Products\EkoUslugi\Domain\Schedule\Services;

use App\Products\EkoUslugi\Domain\Address\Models\ResidentAddress;
use App\Products\EkoUslugi\Domain\Schedule\Models\CollectionScheduleDate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class UpcomingCollectionService
{
    /**
     * @return Collection<int, CollectionScheduleDate>
     */
    public function forAddress(ResidentAddress $address, int $limit = 20): Collection
    {
        Log::info('eko_uslugi.schedule.upcoming.start', [
            'resident_address_id' => $address->id,
            'eko_zone_id' => $address->eko_zone_id,
        ]);

        if ($address->eko_zone_id === null) {
            Log::warning('eko_uslugi.schedule.upcoming.rejected_missing_zone', [
                'resident_address_id' => $address->id,
            ]);

            return CollectionScheduleDate::newCollection();
        }

        $dates = CollectionScheduleDate::query()
            ->with(['fraction', 'zone', 'schedule'])
            ->where('eko_zone_id', $address->eko_zone_id)
            ->whereDate('collection_date', '>=', now()->toDateString())
            ->whereHas('schedule', fn ($query) => $query->active())
            ->orderBy('collection_date')
            ->limit($limit)
            ->get();

        Log::info('eko_uslugi.schedule.upcoming.success', [
            'resident_address_id' => $address->id,
            'dates_count' => $dates->count(),
        ]);

        return $dates;
    }
}
