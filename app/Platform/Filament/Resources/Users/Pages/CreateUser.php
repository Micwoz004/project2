<?php

namespace App\Platform\Filament\Resources\Users\Pages;

use App\Platform\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Platform\Clients\Services\CurrentClient;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $roles = $data['role_names'] ?? [];
        unset($data['role_names']);

        $user = User::query()->create($data);
        $user->ensureClientMembership(app(CurrentClient::class)->require());
        $user->syncRoles($roles);

        return $user;
    }
}
