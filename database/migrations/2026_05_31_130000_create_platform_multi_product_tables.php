<?php

use App\Platform\Clients\Models\Client;
use App\Platform\Products\Enums\ProductKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('client_products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('product_key', 80);
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['client_id', 'product_key']);
            $table->index(['product_key', 'enabled']);
        });

        Schema::create('client_memberships', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['client_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
        });

        $defaultClientId = $this->ensureDefaultClient();
        $this->ensureDefaultProducts($defaultClientId);
        $this->ensureExistingUserMemberships($defaultClientId);
        $this->addPermissionTeamColumns();

        foreach ($this->clientScopedTables() as $tableName) {
            $this->addClientColumn($tableName, $defaultClientId);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->clientScopedTables()) as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'client_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropColumn('client_id');
                });
            }
        }

        Schema::dropIfExists('client_memberships');
        Schema::dropIfExists('client_products');
        Schema::dropIfExists('clients');
    }

    private function ensureDefaultClient(): int
    {
        $defaultClientId = DB::table('clients')->where('slug', Client::DEFAULT_SLUG)->value('id');

        if (is_numeric($defaultClientId)) {
            return (int) $defaultClientId;
        }

        return (int) DB::table('clients')->insertGetId([
            'name' => 'Domyślny klient',
            'slug' => Client::DEFAULT_SLUG,
            'is_active' => true,
            'settings' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureDefaultProducts(int $defaultClientId): void
    {
        foreach (ProductKey::cases() as $productKey) {
            DB::table('client_products')->updateOrInsert(
                [
                    'client_id' => $defaultClientId,
                    'product_key' => $productKey->value,
                ],
                [
                    'enabled' => in_array($productKey->value, config('platform.default_products', []), true),
                    'settings' => json_encode([]),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function ensureExistingUserMemberships(int $defaultClientId): void
    {
        DB::table('users')
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $userId) use ($defaultClientId): void {
                DB::table('client_memberships')->updateOrInsert(
                    [
                        'client_id' => $defaultClientId,
                        'user_id' => $userId,
                    ],
                    [
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            });
    }

    private function addPermissionTeamColumns(): void
    {
        $tableNames = config('permission.table_names');
        $teamColumn = config('permission.column_names.team_foreign_key', 'client_id');

        foreach (['roles', 'model_has_roles', 'model_has_permissions'] as $key) {
            $tableName = $tableNames[$key] ?? null;

            if (! is_string($tableName) || ! Schema::hasTable($tableName) || Schema::hasColumn($tableName, $teamColumn)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($teamColumn): void {
                $table->unsignedBigInteger($teamColumn)->nullable()->index();
            });
        }
    }

    private function addClientColumn(string $tableName, int $defaultClientId): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'client_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->unsignedBigInteger('client_id')->nullable()->index();
        });

        DB::table($tableName)
            ->whereNull('client_id')
            ->update(['client_id' => $defaultClientId]);
    }

    /**
     * @return list<string>
     */
    private function clientScopedTables(): array
    {
        return [
            'departments',
            'budget_editions',
            'project_areas',
            'categories',
            'content_pages',
            'projects',
            'project_cost_items',
            'project_files',
            'project_coauthors',
            'project_versions',
            'verification_assignments',
            'formal_verifications',
            'initial_merit_verifications',
            'final_merit_verifications',
            'consultation_verifications',
            'board_vote_rejections',
            'project_board_votes',
            'voter_registry_hashes',
            'voters',
            'vote_cards',
            'votes',
            'voting_tokens',
            'sms_logs',
            'application_settings',
            'correspondence_messages',
            'project_comments',
            'legacy_import_batches',
            'project_corrections',
            'dictionary_entries',
            'project_change_suggestions',
            'project_notifications',
            'mail_logs',
            'project_public_comments',
            'detailed_verifications',
            'location_verifications',
            'verification_versions',
            'project_user_assignments',
            'advanced_verifications',
            'project_appeals',
            'project_department_recommendations',
            'project_department_scopes',
            'verification_pressure_logs',
            'legacy_audit_logs',
            'project_status_labels',
            'legacy_pesel_records',
            'legacy_pesel_verification_entries',
            'result_tie_decisions',
            'report_exports',
            'result_publications',
            'public_announcements',
            'public_pages',
            'cost_guide_items',
        ];
    }
};
