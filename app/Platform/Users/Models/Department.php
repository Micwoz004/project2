<?php

namespace App\Platform\Users\Models;

use App\Platform\Clients\Concerns\BelongsToClient;

use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use BelongsToClient;

    protected $guarded = [];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'main_department_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
