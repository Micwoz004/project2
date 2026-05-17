<?php

namespace App\Console\Commands;

use App\Domain\LegacyImport\Services\LegacyMysqlImportService;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportLegacyMysqlCommand extends Command
{
    protected $signature = 'sbo:legacy-import-mysql
        {--connection=legacy_mysql : Laravel DB connection pointing at imported legacy MySQL dump}
        {--source=legacy-mysql : Source label stored in legacy_import_batches}
        {--guard=web : Spatie guard name for imported RBAC}
        {--memory-limit=1024M : Runtime memory limit used by the full dump reader}';

    protected $description = 'Import SBO legacy data directly from a MySQL/staging database connection.';

    public function handle(LegacyMysqlImportService $importService): int
    {
        $connection = (string) $this->option('connection');
        $source = (string) $this->option('source');
        $guardName = (string) $this->option('guard');
        $memoryLimit = (string) $this->option('memory-limit');

        ini_set('memory_limit', $memoryLimit);

        Log::info('legacy_mysql_import.command.start', [
            'connection' => $connection,
            'source' => $source,
            'guard' => $guardName,
            'memory_limit' => $memoryLimit,
        ]);

        try {
            $stats = $importService->import($connection, $source, $guardName);
        } catch (DomainException $exception) {
            Log::warning('legacy_mysql_import.command.rejected', [
                'connection' => $connection,
                'message' => $exception->getMessage(),
            ]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            Log::error('legacy_mysql_import.command.failed', [
                'connection' => $connection,
                'exception' => $exception,
            ]);
            $this->error('Legacy MySQL import failed unexpectedly.');

            return self::FAILURE;
        }

        Log::info('legacy_mysql_import.command.success', [
            'connection' => $connection,
            'source' => $source,
            'domain_batch_id' => $stats['domain_batch_id'],
        ]);

        $this->info("Legacy MySQL import batch {$stats['domain_batch_id']} completed.");

        return self::SUCCESS;
    }
}
