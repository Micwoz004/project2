<?php

namespace App\Domain\Verification\Models;

use App\Domain\Projects\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAppeal extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'response_created_at' => 'datetime',
            'first_decision_created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
