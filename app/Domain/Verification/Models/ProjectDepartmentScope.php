<?php

namespace App\Domain\Verification\Models;

use App\Domain\Projects\Models\Project;
use App\Domain\Users\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDepartmentScope extends Model
{
    public const SCOPE_INITIAL = 'initial';

    public const SCOPE_DEPARTMENT = 'department';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'opinion_deadline' => 'datetime',
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
