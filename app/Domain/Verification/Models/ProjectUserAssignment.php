<?php

namespace App\Domain\Verification\Models;

use App\Domain\Projects\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectUserAssignment extends Model
{
    public const ROLE_COORDINATOR = 'coordinator';

    public const ROLE_VERIFIER = 'verifier';

    protected $guarded = [];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
