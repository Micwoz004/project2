<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_products')) {
            DB::table('client_products')
                ->where('product_key', 'eco_services')
                ->update(['product_key' => 'eco_uslugi']);
        }

        if (Schema::hasTable('permissions')) {
            foreach ($this->permissionNames() as $oldName => $newName) {
                DB::table('permissions')
                    ->where('name', $oldName)
                    ->update(['name' => $newName]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('client_products')) {
            DB::table('client_products')
                ->where('product_key', 'eco_uslugi')
                ->update(['product_key' => 'eco_services']);
        }

        if (Schema::hasTable('permissions')) {
            foreach ($this->permissionNames() as $oldName => $newName) {
                DB::table('permissions')
                    ->where('name', $newName)
                    ->update(['name' => $oldName]);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function permissionNames(): array
    {
        return [
            'eco_services.admin.access' => 'eco_uslugi.admin.access',
            'eco_services.zones.manage' => 'eco_uslugi.zones.manage',
            'eco_services.waste.manage' => 'eco_uslugi.waste.manage',
            'eco_services.schedules.manage' => 'eco_uslugi.schedules.manage',
            'eco_services.pszok.manage' => 'eco_uslugi.pszok.manage',
            'eco_services.news.manage' => 'eco_uslugi.news.manage',
            'eco_services.notifications.manage' => 'eco_uslugi.notifications.manage',
            'eco_services.air_quality.manage' => 'eco_uslugi.air_quality.manage',
        ];
    }
};
