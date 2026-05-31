<?php

namespace App\Policies;

use App\Products\CivicBudget\Domain\Settings\Models\CostGuideItem;
use App\Platform\Users\Enums\SystemPermission;
use App\Models\User;

class CostGuideItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesPublicContent($user);
    }

    public function view(User $user, CostGuideItem $costGuideItem): bool
    {
        return $this->managesPublicContent($user);
    }

    public function create(User $user): bool
    {
        return $this->managesPublicContent($user);
    }

    public function update(User $user, CostGuideItem $costGuideItem): bool
    {
        return $this->managesPublicContent($user);
    }

    public function delete(User $user, CostGuideItem $costGuideItem): bool
    {
        return $this->managesPublicContent($user);
    }

    private function managesPublicContent(User $user): bool
    {
        return $user->can(SystemPermission::SettingsManage->value)
            || $user->hasAnyRole(['admin', 'bdo']);
    }
}
