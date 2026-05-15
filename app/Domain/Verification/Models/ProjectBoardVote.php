<?php

namespace App\Domain\Verification\Models;

use App\Domain\Projects\Models\Project;
use App\Domain\Verification\Enums\BoardType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBoardVote extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'board_type' => BoardType::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
