<?php

namespace App\Domain\LegacyImport\Services;

use App\Domain\Users\Services\LegacyRbacImportService;
use App\Domain\Users\Services\LegacyUserImportService;
use Illuminate\Support\Facades\Log;

class LegacyMysqlImportService
{
    public function __construct(
        private readonly LegacyMysqlSourceReader $sourceReader,
        private readonly LegacyUserImportService $userImportService,
        private readonly LegacyFixtureImportService $fixtureImportService,
        private readonly LegacyRbacImportService $rbacImportService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function import(string $connection, string $source, string $guardName = 'web'): array
    {
        Log::info('legacy_mysql_import.start', [
            'connection' => $connection,
            'source' => $source,
            'guard' => $guardName,
        ]);

        $userStats = $this->userImportService->import(
            $this->sourceReader->read($connection, LegacyMysqlSourceReader::USER_TABLES),
        );

        $batch = $this->fixtureImportService->import(
            $this->sourceReader->read($connection, LegacyMysqlSourceReader::DOMAIN_TABLES),
            $source,
        );

        $rbacStats = $this->rbacImportService->import(
            $this->sourceReader->read($connection, LegacyMysqlSourceReader::RBAC_TABLES),
            $guardName,
        );

        $stats = [
            'users' => $userStats,
            'domain_batch_id' => $batch->id,
            'domain' => $batch->stats,
            'rbac' => $rbacStats,
        ];

        Log::info('legacy_mysql_import.success', [
            'connection' => $connection,
            'source' => $source,
            'domain_batch_id' => $batch->id,
        ]);

        return $stats;
    }
}
