<?php

namespace App\Domain\Verification\Models;

use App\Domain\Projects\Models\Project;
use App\Domain\Users\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDepartmentRecommendation extends Model
{
    public const TYPE_PRE = 'pre';

    public const TYPE_WJO = 'wjo';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
