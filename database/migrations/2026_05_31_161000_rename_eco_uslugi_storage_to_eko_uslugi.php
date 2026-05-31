<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameProductKeys('eco_uslugi', 'eko_uslugi');
        $this->renamePermissionKeys('eco_uslugi', 'eko_uslugi');

        foreach ($this->tableNames() as $oldName => $newName) {
            $this->renameTable($oldName, $newName);
        }

        foreach ($this->columnsByTable() as $tableName => $columns) {
            foreach ($columns as $oldName => $newName) {
                $this->renameColumn($tableName, $oldName, $newName);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->columnsByTable()) as $tableName => $columns) {
            foreach ($columns as $oldName => $newName) {
                $this->renameColumn($tableName, $newName, $oldName);
            }
        }

        foreach (array_reverse($this->tableNames()) as $oldName => $newName) {
            $this->renameTable($newName, $oldName);
        }

        $this->renamePermissionKeys('eko_uslugi', 'eco_uslugi');
        $this->renameProductKeys('eko_uslugi', 'eco_uslugi');
    }

    private function renameProductKeys(string $from, string $to): void
    {
        if (! Schema::hasTable('client_products')) {
            return;
        }

        DB::table('client_products')
            ->where('product_key', $from)
            ->update(['product_key' => $to]);
    }

    private function renamePermissionKeys(string $from, string $to): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->where('name', 'like', $from.'.%')
            ->update([
                'name' => DB::raw("replace(name, '{$from}.', '{$to}.')"),
            ]);
    }

    private function renameTable(string $oldName, string $newName): void
    {
        if (Schema::hasTable($oldName) && ! Schema::hasTable($newName)) {
            Schema::rename($oldName, $newName);
        }
    }

    private function renameColumn(string $tableName, string $oldName, string $newName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $oldName) || Schema::hasColumn($tableName, $newName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($oldName, $newName): void {
            $table->renameColumn($oldName, $newName);
        });
    }

    /**
     * @return array<string, string>
     */
    private function tableNames(): array
    {
        return [
            'eco_zones' => 'eko_zones',
            'eco_zone_address_rules' => 'eko_zone_address_rules',
            'eco_resident_addresses' => 'eko_resident_addresses',
            'eco_waste_fractions' => 'eko_waste_fractions',
            'eco_waste_items' => 'eko_waste_items',
            'eco_waste_item_synonyms' => 'eko_waste_item_synonyms',
            'eco_pszok_points' => 'eko_pszok_points',
            'eco_pszok_fraction' => 'eko_pszok_fraction',
            'eco_collection_schedules' => 'eko_collection_schedules',
            'eco_collection_schedule_dates' => 'eko_collection_schedule_dates',
            'eco_news_categories' => 'eko_news_categories',
            'eco_news_posts' => 'eko_news_posts',
            'eco_air_quality_stations' => 'eko_air_quality_stations',
            'eco_air_quality_readings' => 'eko_air_quality_readings',
            'eco_notification_templates' => 'eko_notification_templates',
            'eco_notification_events' => 'eko_notification_events',
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function columnsByTable(): array
    {
        return [
            'eko_zone_address_rules' => [
                'eco_zone_id' => 'eko_zone_id',
            ],
            'eko_resident_addresses' => [
                'eco_zone_id' => 'eko_zone_id',
            ],
            'eko_waste_items' => [
                'eco_waste_fraction_id' => 'eko_waste_fraction_id',
            ],
            'eko_waste_item_synonyms' => [
                'eco_waste_item_id' => 'eko_waste_item_id',
            ],
            'eko_pszok_fraction' => [
                'eco_pszok_point_id' => 'eko_pszok_point_id',
                'eco_waste_fraction_id' => 'eko_waste_fraction_id',
            ],
            'eko_collection_schedule_dates' => [
                'eco_collection_schedule_id' => 'eko_collection_schedule_id',
                'eco_zone_id' => 'eko_zone_id',
                'eco_waste_fraction_id' => 'eko_waste_fraction_id',
            ],
            'eko_news_posts' => [
                'eco_news_category_id' => 'eko_news_category_id',
                'eco_zone_id' => 'eko_zone_id',
            ],
            'eko_air_quality_readings' => [
                'eco_air_quality_station_id' => 'eko_air_quality_station_id',
            ],
            'eko_notification_events' => [
                'eco_notification_template_id' => 'eko_notification_template_id',
            ],
        ];
    }
};
