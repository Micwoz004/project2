<?php

namespace App\Domain\BudgetEditions\Models;

use App\Domain\Projects\Models\Project;
use App\Domain\Results\Models\ResultPublication;
use App\Domain\Settings\Models\ContentPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetEdition extends Model
{
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
