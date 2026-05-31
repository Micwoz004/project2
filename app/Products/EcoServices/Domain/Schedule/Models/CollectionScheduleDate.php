<?php

namespace App\Products\EcoServices\Domain\Schedule\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use App\Products\EcoServices\Domain\Address\Models\EcoZone;
use App\Products\EcoServices\Domain\Waste\Models\WasteFraction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionScheduleDate extends Model
{
    use BelongsToClient;

    protected $table = 'eco_collection_schedule_dates';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(CollectionSchedule::class, 'eco_collection_schedule_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(EcoZone::class, 'eco_zone_id');
    }

    public function fraction(): BelongsTo
    {
        return $this->belongsTo(WasteFraction::class, 'eco_waste_fraction_id');
    }
}
