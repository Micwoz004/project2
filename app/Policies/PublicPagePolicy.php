<?php

namespace App\Policies;

use App\Products\CivicBudget\Domain\Settings\Models\PublicPage;
use App\Platform\Users\Enums\SystemPermission;
use App\Models\User;

class PublicPagePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesPublicContent($user);
    }

    public function view(User $user, PublicPage $publicPage): bool
    {
        return $this->managesPublicContent($user);
    }

    public function create(User $user): bool
    {
        return $this->managesPublicContent($user);
    }

    public function update(User $user, PublicPage $publicPage): bool
    {
        return $this->managesPublicContent($user);
    }

    public function delete(User $user, PublicPage $publicPage): bool
    {
        return $this->managesPublicContent($user);
    }

    private function managesPublicContent(User $user): bool
    {
        return $user->can(SystemPermission::SettingsManage->value)
            || $user->hasAnyRole(['admin', 'bdo']);
    }
}
