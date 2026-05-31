<?php

namespace App\Products\EkoUslugi\Domain\Schedule\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CollectionSchedule extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $table = 'eko_collection_schedules';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function dates(): HasMany
    {
        return $this->hasMany(CollectionScheduleDate::class, 'eko_collection_schedule_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
