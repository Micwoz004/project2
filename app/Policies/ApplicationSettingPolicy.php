<?php

namespace App\Policies;

use App\Domain\Settings\Models\ApplicationSetting;
use App\Domain\Users\Enums\SystemPermission;
use App\Models\User;

class ApplicationSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesSettings($user);
    }

    public function view(User $user, ApplicationSetting $applicationSetting): bool
    {
        return $this->managesSettings($user);
    }

    public function create(User $user): bool
    {
        return $this->managesSettings($user);
    }

    public function update(User $user, ApplicationSetting $applicationSetting): bool
    {
        return $this->managesSettings($user);
    }

    public function delete(User $user, ApplicationSetting $applicationSetting): bool
    {
        return $this->managesSettings($user);
    }

    private function managesSettings(User $user): bool
    {
        return $user->can(SystemPermission::SettingsManage->value)
            || $user->hasAnyRole(['admin', 'bdo']);
    }
}
