<?php

namespace App\Console\Commands;

use App\Platform\Clients\Models\Client;
use App\Platform\Clients\Services\CurrentClient;
use App\Products\EkoUslugi\Domain\AirQuality\Actions\SyncAirQualityStationsAction;
use Illuminate\Console\Command;

class SyncEkoAirQualityCommand extends Command
{
    protected $signature = 'eko-uslugi:sync-air-quality {--client=default : Slug klienta platformy}';

    protected $description = 'Synchronizuje stacje i indeks jakości powietrza GIOŚ dla produktu Eko usługi.';

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
