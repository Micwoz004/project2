<?php

namespace App\Policies;

use App\Domain\Projects\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesDictionaries($user);
    }

    public function view(User $user, Category $category): bool
    {
        return $this->managesDictionaries($user);
    }

    public function create(User $user): bool
    {
        return $this->managesDictionaries($user);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->managesDictionaries($user);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->managesDictionaries($user);
    }

    private function managesDictionaries(User $user): bool
    {
        return $user->can('dictionaries.manage') || $user->hasAnyRole(['admin', 'bdo']);
    }
}
