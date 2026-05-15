<?php

namespace App\Policies;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Models\User;

class BudgetEditionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesBudgetEditions($user);
    }

    public function view(User $user, BudgetEdition $budgetEdition): bool
    {
        return $this->managesBudgetEditions($user);
    }

    public function create(User $user): bool
    {
        return $this->managesBudgetEditions($user);
    }

    public function update(User $user, BudgetEdition $budgetEdition): bool
    {
        return $this->managesBudgetEditions($user);
    }

    public function delete(User $user, BudgetEdition $budgetEdition): bool
    {
        return $this->managesBudgetEditions($user);
    }

    private function managesBudgetEditions(User $user): bool
    {
        return $user->can('budget_editions.manage') || $user->hasAnyRole(['admin', 'bdo']);
    }
}
