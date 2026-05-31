<?php

namespace Database\Seeders;

use App\Platform\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Platform\Clients\Services\CurrentClient;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(
        SyncSystemRolesAndPermissionsAction $syncSystemRolesAndPermissions,
        CurrentClient $currentClient,
    ): void
    {
        $syncSystemRolesAndPermissions->execute();
        $client = $currentClient->require();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->ensureClientMembership($client);
        $user->assignRole('admin');
    }
}
