<?php

namespace App\Policies;

use App\Domain\Results\Models\ResultPublication;
use App\Domain\Users\Enums\SystemPermission;
use App\Models\User;

class ResultPublicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->viewsResults($user);
    }

    public function view(User $user, ResultPublication $resultPublication): bool
    {
        return $this->viewsResults($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ResultPublication $resultPublication): bool
    {
        return false;
    }

    public function delete(User $user, ResultPublication $resultPublication): bool
    {
        return false;
    }

    private function viewsResults(User $user): bool
    {
        return $user->can(SystemPermission::ResultsView->value)
            || $user->can(SystemPermission::ReportsExport->value)
            || $user->hasAnyRole(['admin', 'bdo']);
    }
}
