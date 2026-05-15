<?php

namespace App\Domain\Projects\Models;

use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCorrection extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'allowed_fields' => 'array',
            'correction_deadline' => 'datetime',
            'correction_done' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function allows(ProjectCorrectionField $field): bool
    {
        return in_array($field->value, $this->allowed_fields, true);
    }
}
