<?php

namespace App\Console\Commands;

use App\Platform\Clients\Models\Client;
use App\Platform\Clients\Services\CurrentClient;
use App\Products\EcoServices\Domain\Notifications\Actions\QueueCollectionReminderNotificationsAction;
use Illuminate\Console\Command;

class QueueEcoCollectionRemindersCommand extends Command
{
    protected $signature = 'eco-services:queue-reminders {--client=default : Slug klienta platformy}';

    protected $description = 'Tworzy zdarzenia przypomnień o odbiorze odpadów dla produktu Ekousługi.';

    public function handle(CurrentClient $currentClient, QueueCollectionReminderNotificationsAction $queueReminders): int
    {
        $client = Client::query()->where('slug', (string) $this->option('client'))->first();

        if (! $client instanceof Client) {
            $this->error('Nie znaleziono klienta platformy.');

            return self::FAILURE;
        }

        $currentClient->set($client);
        $stats = $queueReminders->execute();

        $this->info("Utworzono {$stats['events']} zdarzeń powiadomień.");

        return self::SUCCESS;
    }
}
