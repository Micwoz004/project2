<?php

namespace App\Products\CivicBudget\Domain\Verification\Models;

use App\Platform\Clients\Concerns\BelongsToClient;

use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Platform\Users\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvancedVerification extends Model
{
    use BelongsToClient;

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
