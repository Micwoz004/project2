<?php

namespace App\Policies;

use App\Domain\Settings\Models\ContentPage;
use App\Domain\Users\Enums\SystemPermission;
use App\Models\User;

class ContentPagePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesSettings($user);
    }

    public function view(User $user, ContentPage $contentPage): bool
    {
        return $this->managesSettings($user);
    }

    public function create(User $user): bool
    {
        return $this->managesSettings($user);
    }

    public function update(User $user, ContentPage $contentPage): bool
    {
        return $this->managesSettings($user);
    }

    public function delete(User $user, ContentPage $contentPage): bool
    {
        return $this->managesSettings($user);
    }

    private function managesSettings(User $user): bool
    {
        return $user->can(SystemPermission::SettingsManage->value)
            || $user->hasAnyRole(['admin', 'bdo']);
    }
}
