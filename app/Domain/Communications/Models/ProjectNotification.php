<?php

namespace App\Domain\Communications\Models;

use App\Domain\Projects\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectNotification extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
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

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_to_user_id');
    }
}
