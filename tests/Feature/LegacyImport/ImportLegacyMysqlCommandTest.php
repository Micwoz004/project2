<?php

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\LegacyImport\Models\LegacyImportBatch;
use App\Domain\LegacyImport\Services\LegacyImportCountComparator;
use App\Domain\Projects\Models\ProjectArea;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

it('imports legacy data directly from a configured mysql-style connection', function (): void {
    $connection = 'legacy_testing';
    configureLegacySqliteConnection($connection);
    createLegacyMysqlFixtureSchema($connection);

    legacySchema($connection)->table('departments')->insert([
        'id' => 3,
        'name' => 'Biuro Dialogu Obywatelskiego',
    ]);
    legacySchema($connection)->table('users')->insert([
        'id' => 7,
        'name' => 'legacy_admin',
        'email' => 'legacy-admin@example.test',
        'status' => 1,
        'firstname' => 'Anna',
        'lastname' => 'Nowak',
        'departmentId' => 3,
    ]);
    legacySchema($connection)->table('authitem')->insert([
        'name' => 'admin',
        'type' => 2,
    ]);
    legacySchema($connection)->table('authassignment')->insert([
        'itemname' => 'admin',
        'userid' => 7,
    ]);
    legacySchema($connection)->table('taskgroups')->insert([
        'id' => 10,
        'proposeStart' => '2025-01-01 00:00:00',
        'proposeEnd' => '2025-02-01 00:00:00',
        'preVotingVerificationEnd' => '2025-03-01 00:00:00',
        'votingStart' => '2025-04-01 00:00:00',
        'votingEnd' => '2025-04-15 23:59:59',
        'postVotingVerificationEnd' => '2025-05-01 00:00:00',
        'resultAnnouncementEnd' => '2025-06-01 00:00:00',
        'currentDigitalCardNo' => 0,
        'currentPaperCardNo' => 0,
    ]);
    legacySchema($connection)->table('tasktypes')->insert([
        'id' => 35,
        'name' => 'Projekt Zielonego SBO',
        'symbol' => 'PZS',
        'nameshortcut' => 'Zielone',
        'local' => 0,
        'costLimit' => 2580000,
        'costLimitSmall' => 0,
        'costLimitBig' => 0,
    ]);

    $this->artisan('sbo:legacy-import-mysql', [
        '--connection' => $connection,
        '--source' => 'legacy-sqlite-test',
    ])->assertSuccessful();

    $user = User::query()->where('legacy_id', 7)->firstOrFail();
    $batch = LegacyImportBatch::query()->firstOrFail();

    expect($user->first_name)->toBe('Anna')
        ->and($user->department?->legacy_id)->toBe(3)
        ->and($user->hasRole('admin'))->toBeTrue()
        ->and(BudgetEdition::query()->where('legacy_id', 10)->exists())->toBeTrue()
        ->and(ProjectArea::query()->where('legacy_id', 35)->firstOrFail()->name_shortcut)->toBe('Zielone')
        ->and($batch->source_path)->toBe('legacy-sqlite-test')
        ->and($batch->stats['taskgroups'])->toBe(1)
        ->and($batch->stats['tasktypes'])->toBe(1);

    $comparison = app(LegacyImportCountComparator::class)->compare($connection);
    $taskgroups = collect($comparison['rows'])->firstWhere('legacy_table', 'taskgroups');
    $tasktypes = collect($comparison['rows'])->firstWhere('legacy_table', 'tasktypes');

    expect($taskgroups['status'])->toBe('matched')
        ->and($taskgroups['source_count'])->toBe(1)
        ->and($taskgroups['target_count'])->toBe(1)
        ->and($tasktypes['status'])->toBe('matched')
        ->and($tasktypes['source_count'])->toBe(1)
        ->and($tasktypes['target_count'])->toBe(1);

    $this->artisan('sbo:legacy-import-counts', [
        '--connection' => $connection,
        '--json' => true,
    ])->assertSuccessful();
});

it('fails the legacy count comparison when comparable target counts differ', function (): void {
    $connection = 'legacy_testing_mismatch';
    configureLegacySqliteConnection($connection);
    createLegacyMysqlFixtureSchema($connection);

    legacySchema($connection)->table('taskgroups')->insert([
        'id' => 10,
        'proposeStart' => '2025-01-01 00:00:00',
        'proposeEnd' => '2025-02-01 00:00:00',
        'preVotingVerificationEnd' => '2025-03-01 00:00:00',
        'votingStart' => '2025-04-01 00:00:00',
        'votingEnd' => '2025-04-15 23:59:59',
        'postVotingVerificationEnd' => '2025-05-01 00:00:00',
        'resultAnnouncementEnd' => '2025-06-01 00:00:00',
        'currentDigitalCardNo' => 0,
        'currentPaperCardNo' => 0,
    ]);

    budgetEdition(['legacy_id' => 10]);
    budgetEdition(['legacy_id' => 11]);

    $this->artisan('sbo:legacy-import-counts', [
        '--connection' => $connection,
        '--fail-on-mismatch' => true,
    ])->assertFailed();
});

function configureLegacySqliteConnection(string $connection): void
{
    $database = storage_path("framework/testing/{$connection}.sqlite");
    File::ensureDirectoryExists(dirname($database));
    File::delete($database);
    File::put($database, '');

    config([
        "database.connections.{$connection}" => [
            'driver' => 'sqlite',
            'database' => $database,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ],
    ]);
}

function createLegacyMysqlFixtureSchema(string $connection): void
{
    Schema::connection($connection)->create('departments', function ($table): void {
        $table->unsignedInteger('id');
        $table->string('name');
    });

    Schema::connection($connection)->create('users', function ($table): void {
        $table->unsignedInteger('id');
        $table->string('name');
        $table->string('email');
        $table->unsignedTinyInteger('status')->default(1);
        $table->string('firstname')->nullable();
        $table->string('lastname')->nullable();
        $table->unsignedInteger('departmentId')->nullable();
    });

    Schema::connection($connection)->create('authitem', function ($table): void {
        $table->string('name');
        $table->unsignedTinyInteger('type');
    });

    Schema::connection($connection)->create('authassignment', function ($table): void {
        $table->string('itemname');
        $table->unsignedInteger('userid');
    });

    Schema::connection($connection)->create('taskgroups', function ($table): void {
        $table->unsignedInteger('id');
        $table->dateTime('proposeStart');
        $table->dateTime('proposeEnd');
        $table->dateTime('preVotingVerificationEnd');
        $table->dateTime('votingStart');
        $table->dateTime('votingEnd');
        $table->dateTime('postVotingVerificationEnd');
        $table->dateTime('resultAnnouncementEnd')->nullable();
        $table->unsignedInteger('currentDigitalCardNo')->default(0);
        $table->unsignedInteger('currentPaperCardNo')->default(0);
    });

    Schema::connection($connection)->create('tasktypes', function ($table): void {
        $table->unsignedInteger('id');
        $table->text('name');
        $table->string('symbol', 8);
        $table->string('nameshortcut')->nullable();
        $table->unsignedTinyInteger('local')->default(1);
        $table->unsignedInteger('costLimit')->nullable();
        $table->float('costLimitSmall')->default(0);
        $table->float('costLimitBig')->default(0);
    });
}

function legacySchema(string $connection): ConnectionInterface
{
    return DB::connection($connection);
}
