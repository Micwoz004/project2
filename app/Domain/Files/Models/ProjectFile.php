<?php

namespace App\Domain\Files\Models;

use App\Domain\Files\Enums\ProjectFileType;
use App\Domain\Projects\Models\Project;
use App\Domain\Users\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
