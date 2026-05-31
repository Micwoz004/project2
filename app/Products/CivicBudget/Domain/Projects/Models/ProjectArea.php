<?php

namespace App\Products\CivicBudget\Domain\Projects\Models;

use App\Platform\Clients\Concerns\BelongsToClient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectArea extends Model
{
    use BelongsToClient;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_local' => 'boolean',
            'cost_limit_small' => 'decimal:2',
            'cost_limit_big' => 'decimal:2',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
