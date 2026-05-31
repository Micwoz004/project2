<?php

namespace App\Products\CivicBudget\Domain\Results\Models;

use App\Platform\Clients\Concerns\BelongsToClient;

use App\Products\CivicBudget\Domain\BudgetEditions\Models\BudgetEdition;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultPublication extends Model
{
    use BelongsToClient;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'project_totals' => 'array',
            'area_totals' => 'array',
            'category_totals' => 'array',
            'status_counts' => 'array',
            'tie_groups' => 'array',
            'category_differences' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function budgetEdition(): BelongsTo
    {
        return $this->belongsTo(BudgetEdition::class);
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_id');
    }
}
