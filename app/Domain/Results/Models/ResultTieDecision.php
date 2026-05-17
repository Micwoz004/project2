<?php

namespace App\Domain\Results\Models;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultTieDecision extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'project_ids' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @param  list<int>  $projectIds
     */
    public static function groupKey(int $points, array $projectIds): string
    {
        sort($projectIds);

        return $points.':'.implode(',', $projectIds);
    }

    public function budgetEdition(): BelongsTo
    {
        return $this->belongsTo(BudgetEdition::class);
    }

    public function winnerProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'winner_project_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }
}
