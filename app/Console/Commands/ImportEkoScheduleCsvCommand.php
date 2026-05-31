<?php

namespace App\Console\Commands;

use App\Platform\Clients\Models\Client;
use App\Platform\Clients\Services\CurrentClient;
use App\Products\EkoUslugi\Domain\Schedule\Actions\ImportCollectionScheduleCsvAction;
use Illuminate\Console\Command;

class ImportEkoScheduleCsvCommand extends Command
{
    protected $signature = 'eko-uslugi:import-schedule
        {path : Ścieżka do pliku CSV}
        {--client=default : Slug klienta platformy}
        {--name= : Nazwa harmonogramu}
        {--replace : Usuń wcześniejsze terminy tego harmonogramu przed importem}';

    protected $description = 'Importuje terminy odbioru odpadów z pliku CSV dla produktu Eko usługi.';

    public function handle(CurrentClient $currentClient, ImportCollectionScheduleCsvAction $importer): int
    {
        $client = Client::query()->where('slug', (string) $this->option('client'))->first();

        if (! $client instanceof Client) {
            $this->error('Nie znaleziono klienta platformy.');

            return self::FAILURE;
        }

        $currentClient->set($client);

        $path = (string) $this->argument('path');
        $scheduleName = (string) ($this->option('name') ?: 'Harmonogram '.now()->year);
        $stats = $importer->execute($path, $scheduleName, (bool) $this->option('replace'));

        $this->info("Zaimportowano {$stats['imported']} terminów, pominięto {$stats['skipped']}.");

        return self::SUCCESS;
    }
}
