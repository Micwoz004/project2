<?php

use App\Domain\LegacyImport\Services\LegacySqlDumpTableCounter;
use Illuminate\Support\Facades\File;

it('counts rows from raw mysql dump without exposing row payloads', function (): void {
    $path = legacySqlDumpFixturePath('legacy-counts.sql');

    writeLegacySqlDumpFixture($path, <<<'SQL'
CREATE TABLE `taskgroups` (`id` int);
INSERT INTO `taskgroups` VALUES
(1,'plain value'),
(2,'value with ), comma, semicolon ; and escaped quote \' inside');
CREATE TABLE IF NOT EXISTS `tasktypes` (`id` int, `name` varchar(255));
INSERT INTO `tasktypes` (`id`, `name`) VALUES (35,'Zielone'),(36,'Kulturalne');
CREATE TABLE `emptytable` (`id` int);
CREATE TABLE `authassignment` (`itemname` varchar(64), `userid` int);
INSERT INTO `authassignment` VALUES ('admin',7),('bdo',8);
SQL);

    $counts = app(LegacySqlDumpTableCounter::class)->count($path);

    expect($counts)->toMatchArray([
        'authassignment' => 2,
        'emptytable' => 0,
        'taskgroups' => 2,
        'tasktypes' => 2,
    ]);

    $this->artisan('sbo:legacy-dump-counts', [
        'path' => $path,
        '--json' => true,
    ])->assertSuccessful();
});

it('compares raw dump counts with migrated target tables', function (): void {
    $path = legacySqlDumpFixturePath('legacy-counts-match.sql');

    writeLegacySqlDumpFixture($path, <<<'SQL'
CREATE TABLE `taskgroups` (`id` int);
INSERT INTO `taskgroups` VALUES (10),(11);
SQL);

    budgetEdition(['legacy_id' => 10]);
    budgetEdition(['legacy_id' => 11]);

    $this->artisan('sbo:legacy-dump-counts', [
        'path' => $path,
        '--compare-target' => true,
        '--fail-on-mismatch' => true,
    ])->assertSuccessful();
});

it('fails raw dump count comparison when target counts differ', function (): void {
    $path = legacySqlDumpFixturePath('legacy-counts-mismatch.sql');

    writeLegacySqlDumpFixture($path, <<<'SQL'
CREATE TABLE `taskgroups` (`id` int);
INSERT INTO `taskgroups` VALUES (10),(11);
SQL);

    budgetEdition(['legacy_id' => 10]);

    $this->artisan('sbo:legacy-dump-counts', [
        'path' => $path,
        '--compare-target' => true,
        '--fail-on-mismatch' => true,
    ])->assertFailed();
});

function legacySqlDumpFixturePath(string $name): string
{
    File::ensureDirectoryExists(storage_path('framework/testing'));

    return storage_path('framework/testing/'.$name);
}

function writeLegacySqlDumpFixture(string $path, string $sql): void
{
    File::put($path, $sql.PHP_EOL);
}
