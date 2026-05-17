<?php

namespace App\Policies;

use App\Domain\Dictionaries\Models\DictionaryEntry;
use App\Domain\Users\Enums\SystemPermission;
use App\Models\User;

class DictionaryEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesDictionaries($user);
    }

    public function view(User $user, DictionaryEntry $dictionaryEntry): bool
    {
        return $this->managesDictionaries($user);
    }

    public function create(User $user): bool
    {
        return $this->managesDictionaries($user);
    }

    public function update(User $user, DictionaryEntry $dictionaryEntry): bool
    {
        return $this->managesDictionaries($user);
    }

    public function delete(User $user, DictionaryEntry $dictionaryEntry): bool
    {
        return $this->managesDictionaries($user);
    }

    private function managesDictionaries(User $user): bool
    {
        return $user->can(SystemPermission::DictionariesManage->value)
            || $user->hasAnyRole(['admin', 'bdo']);
    }
}
