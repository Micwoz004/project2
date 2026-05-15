<?php

namespace App\Domain\Voting\Models;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Voting\Enums\CitizenConfirmation;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoteCard extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'statement' => 'boolean',
            'terms_accepted' => 'boolean',
            'city_statement' => 'boolean',
            'no_pesel_number' => 'boolean',
            'digital' => 'boolean',
            'status' => VoteCardStatus::class,
            'checkout_date_time' => 'datetime',
            'citizen_confirm' => CitizenConfirmation::class,
            'parent_confirm' => 'boolean',
        ];
    }

    public function budgetEdition(): BelongsTo
    {
        return $this->belongsTo(BudgetEdition::class);
    }

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function checkoutUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checkout_user_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
