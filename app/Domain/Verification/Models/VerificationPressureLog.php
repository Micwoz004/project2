<?php

namespace App\Domain\Verification\Models;

use App\Domain\Projects\Models\Project;
use App\Domain\Users\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationPressureLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'sent_to' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
