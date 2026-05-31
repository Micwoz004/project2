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
                ->update(['product_key' => 'eko_uslugi']);
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
                ->where('product_key', 'eko_uslugi')
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
            'eco_services.admin.access' => 'eko_uslugi.admin.access',
            'eco_services.zones.manage' => 'eko_uslugi.zones.manage',
            'eco_services.waste.manage' => 'eko_uslugi.waste.manage',
            'eco_services.schedules.manage' => 'eko_uslugi.schedules.manage',
            'eco_services.pszok.manage' => 'eko_uslugi.pszok.manage',
            'eco_services.news.manage' => 'eko_uslugi.news.manage',
            'eco_services.notifications.manage' => 'eko_uslugi.notifications.manage',
            'eco_services.air_quality.manage' => 'eko_uslugi.air_quality.manage',
        ];
    }
};
