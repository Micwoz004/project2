<?php

namespace App\Domain\Verification\Models;

use App\Domain\Projects\Models\Project;
use App\Domain\Users\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvancedVerification extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'raw_legacy_payload' => 'array',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
