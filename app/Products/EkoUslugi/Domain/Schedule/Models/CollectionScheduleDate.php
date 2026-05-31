<?php

namespace App\Products\EkoUslugi\Domain\Schedule\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use App\Products\EkoUslugi\Domain\Address\Models\EkoZone;
use App\Products\EkoUslugi\Domain\Waste\Models\WasteFraction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionScheduleDate extends Model
{
    use BelongsToClient;

    protected $table = 'eko_collection_schedule_dates';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(CollectionSchedule::class, 'eko_collection_schedule_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(EkoZone::class, 'eko_zone_id');
    }

    public function fraction(): BelongsTo
    {
        return $this->belongsTo(WasteFraction::class, 'eko_waste_fraction_id');
    }
}
