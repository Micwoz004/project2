<?php

namespace App\Console\Commands;

use App\Platform\Clients\Models\Client;
use App\Platform\Clients\Services\CurrentClient;
use App\Products\EcoUslugi\Domain\AirQuality\Actions\SyncAirQualityStationsAction;
use Illuminate\Console\Command;

class SyncEcoAirQualityCommand extends Command
{
    protected $signature = 'eco-uslugi:sync-air-quality {--client=default : Slug klienta platformy}';

    protected $description = 'Synchronizuje stacje i indeks jakości powietrza GIOŚ dla produktu Ekousługi.';

    public function handle(CurrentClient $currentClient, SyncAirQualityStationsAction $sync): int
    {
        $client = Client::query()->where('slug', (string) $this->option('client'))->first();

        if (! $client instanceof Client) {
            $this->error('Nie znaleziono klienta platformy.');

            return self::FAILURE;
        }

        $currentClient->set($client);
        $stats = $sync->execute();

        $this->info("Zsynchronizowano {$stats['stations']} stacji i {$stats['readings']} odczytów.");

        return self::SUCCESS;
    }
}
