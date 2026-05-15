<?php

namespace App\Domain\Files\Models;

use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Projects\Models\Project;
use App\Domain\Users\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectFile extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => ProjectFileType::class,
            'is_private' => 'boolean',
            'is_task_form_attachment' => 'boolean',
            'is_pre_verification_attachment' => 'boolean',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('is_private', false)
            ->whereHas('project', fn (Builder $projectQuery): Builder => $projectQuery->where('attachments_anonymized', true));
    }

    public function publicUrl(): ?string
    {
        if ($this->is_private || ! $this->project->attachments_anonymized) {
            return null;
        }

        return Storage::disk('public')->url($this->stored_name);
    }
}
