<?php

namespace App\Console\Commands;

use App\Platform\Clients\Models\Client;
use App\Platform\Clients\Services\CurrentClient;
use App\Products\EcoUslugi\Domain\Schedule\Actions\ImportCollectionScheduleCsvAction;
use Illuminate\Console\Command;

class ImportEcoScheduleCsvCommand extends Command
{
    protected $signature = 'eco-uslugi:import-schedule
        {path : Ścieżka do pliku CSV}
        {--client=default : Slug klienta platformy}
        {--name= : Nazwa harmonogramu}
        {--replace : Usuń wcześniejsze terminy tego harmonogramu przed importem}';

    protected $description = 'Importuje terminy odbioru odpadów z pliku CSV dla produktu Ekousługi.';

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
