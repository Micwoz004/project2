<?php

namespace App\Domain\Verification\Models;

use App\Domain\Projects\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationVerification extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'has_recommendations' => 'boolean',
            'recommendations_at' => 'datetime',
            'verified_at' => 'datetime',
            'is_public' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function modifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by_id');
    }
}
