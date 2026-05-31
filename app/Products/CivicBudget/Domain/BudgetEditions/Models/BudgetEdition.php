<?php

namespace App\Products\CivicBudget\Domain\BudgetEditions\Models;

use App\Platform\Clients\Concerns\BelongsToClient;

use App\Products\CivicBudget\Domain\BudgetEditions\Enums\BudgetEditionStatus;
use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Products\CivicBudget\Domain\Results\Models\ResultPublication;
use App\Products\CivicBudget\Domain\Settings\Models\ContentPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetEdition extends Model
{
    use BelongsToClient;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'propose_start' => 'datetime',
            'propose_end' => 'datetime',
            'pre_voting_verification_end' => 'datetime',
            'voting_start' => 'datetime',
            'voting_end' => 'datetime',
            'post_voting_verification_end' => 'datetime',
            'result_announcement_end' => 'datetime',
            'is_project_number_drawing' => 'boolean',
            'status' => BudgetEditionStatus::class,
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function contentPages(): HasMany
    {
        return $this->hasMany(ContentPage::class);
    }

    public function resultPublications(): HasMany
    {
        return $this->hasMany(ResultPublication::class);
    }
}
