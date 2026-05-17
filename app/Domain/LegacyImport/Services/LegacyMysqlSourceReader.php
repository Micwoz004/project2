<?php

namespace App\Domain\LegacyImport\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LegacyMysqlSourceReader
{
    public const USER_TABLES = [
        'departments',
        'users',
    ];

    public const RBAC_TABLES = [
        'authitem',
        'authitemchild',
        'authassignment',
    ];

    public const DOMAIN_TABLES = [
        'taskgroups',
        'settings',
        'pages',
        'statuses',
        'activations',
        'pesel',
        'verification',
        'tasktypes',
        'categories',
        'tasks',
        'logs',
        'taskscategories',
        'taskcosts',
        'files',
        'filesprivate',
        'cocreators',
        'taskverification',
        'taskinitialmeritverification',
        'taskfinishmeritverification',
        'taskconsultation',
        'detailedverification',
        'locationverification',
        'verificationversion',
        'taskadvancedverification',
        'prerecommendations',
        'recommendationswjo',
        'tasksinitialverification',
        'tasksdepartments',
        'coordinatorassignment',
        'verifierassignment',
        'taskdepartmentassignment',
        'verificationpressure',
        'zkvotes',
        'otvotes',
        'atvotes',
        'atotvotesrejection',
        'taskappealagainstdecision',
        'correspondence',
        'taskcomments',
        'comments',
        'notification',
        'maillogs',
        'taskcorrection',
        'taskchangessuggestion',
        'versions',
        'newverification',
        'votingtokens',
        'voters',
        'smslogs',
        'votecards',
        'votes',
    ];

    /**
     * @param  list<string>  $tables
     * @return array<string, list<array<string, mixed>>>
     */
    public function read(string $connection, array $tables): array
    {
        Log::info('legacy_mysql_source.read.start', [
            'connection' => $connection,
            'tables_count' => count($tables),
        ]);

        $payload = [];

        foreach ($tables as $table) {
            if (! Schema::connection($connection)->hasTable($table)) {
                Log::warning('legacy_mysql_source.table_missing', [
                    'connection' => $connection,
                    'table' => $table,
                ]);

                $payload[$table] = [];

                continue;
            }

            $rows = DB::connection($connection)
                ->table($table)
                ->get()
                ->map(fn (object $row): array => $this->normalizeRow($table, (array) $row))
                ->values()
                ->all();

            $payload[$table] = $rows;

            Log::info('legacy_mysql_source.table_read', [
                'connection' => $connection,
                'table' => $table,
                'rows_count' => count($rows),
            ]);
        }

        Log::info('legacy_mysql_source.read.success', [
            'connection' => $connection,
            'tables_count' => count($payload),
        ]);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(string $table, array $row): array
    {
        foreach ($this->aliasesFor($table) as $legacyColumn => $normalizedColumn) {
            if (array_key_exists($legacyColumn, $row) && ! array_key_exists($normalizedColumn, $row)) {
                $row[$normalizedColumn] = $row[$legacyColumn];
            }
        }

        return $row;
    }

    /**
     * @return array<string, string>
     */
    private function aliasesFor(string $table): array
    {
        return match ($table) {
            'tasktypes' => [
                'nameshortcut' => 'nameShortcut',
            ],
            'users' => [
                'name' => 'username',
                'firstname' => 'firstName',
                'lastname' => 'lastName',
                'houseno' => 'houseNo',
                'homeno' => 'flatNo',
                'postcode' => 'postCode',
            ],
            'cocreators' => [
                'firstname' => 'firstName',
                'lastname' => 'lastName',
                'houseno' => 'houseNo',
                'homeno' => 'flatNo',
                'postcode' => 'postCode',
            ],
            default => [],
        };
    }
}
