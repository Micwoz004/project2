<?php

namespace App\Domain\Verification\Models;

use App\Domain\Projects\Models\Project;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Enums\VerificationAssignmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationAssignment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'sent_at' => 'datetime',
            'is_returned' => 'boolean',
            'type' => VerificationAssignmentType::class,
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
