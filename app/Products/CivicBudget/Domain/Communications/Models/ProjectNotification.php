<?php

namespace App\Products\CivicBudget\Domain\Communications\Models;

use App\Platform\Clients\Concerns\BelongsToClient;

use App\Products\CivicBudget\Domain\Communications\Enums\ProjectNotificationTemplate;
use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectNotification extends Model
{
    use BelongsToClient;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'template' => ProjectNotificationTemplate::class,
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
