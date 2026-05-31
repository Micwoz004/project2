<?php

namespace App\Policies;

use App\Products\CivicBudget\Domain\Settings\Models\PublicAnnouncement;
use App\Platform\Users\Enums\SystemPermission;
use App\Models\User;

class PublicAnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesPublicContent($user);
    }

    public function view(User $user, PublicAnnouncement $publicAnnouncement): bool
    {
        return $this->managesPublicContent($user);
    }

    public function create(User $user): bool
    {
        return $this->managesPublicContent($user);
    }

    public function update(User $user, PublicAnnouncement $publicAnnouncement): bool
    {
        return $this->managesPublicContent($user);
    }

    public function delete(User $user, PublicAnnouncement $publicAnnouncement): bool
    {
        return $this->managesPublicContent($user);
    }

    private function managesPublicContent(User $user): bool
    {
        return $user->can(SystemPermission::SettingsManage->value)
            || $user->hasAnyRole(['admin', 'bdo']);
    }
}
