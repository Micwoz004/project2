<?php

namespace App\Domain\Projects\Models;

use App\Domain\Projects\Enums\ProjectChangeSuggestionDecision;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectChangeSuggestion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'old_data' => 'array',
            'old_costs' => 'array',
            'old_files' => 'array',
            'new_data' => 'array',
            'new_costs' => 'array',
            'new_files' => 'array',
            'is_accepted_by_admin' => 'boolean',
            'deadline' => 'datetime',
            'decision' => ProjectChangeSuggestionDecision::class,
            'decision_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function decisionUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by_id');
    }
}
