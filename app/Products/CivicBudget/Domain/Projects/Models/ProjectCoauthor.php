<?php

namespace App\Products\CivicBudget\Domain\Projects\Models;

use App\Platform\Clients\Concerns\BelongsToClient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCoauthor extends Model
{
    use BelongsToClient;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'personal_data_agree' => 'boolean',
            'name_agree' => 'boolean',
            'data_evaluation_agree' => 'boolean',
            'read_confirm' => 'boolean',
            'confirm' => 'boolean',
            'email_agree' => 'boolean',
            'phone_agree' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
