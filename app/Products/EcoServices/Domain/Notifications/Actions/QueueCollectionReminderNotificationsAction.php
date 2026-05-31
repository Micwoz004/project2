<?php

namespace App\Products\EcoServices\Domain\Notifications\Actions;

use App\Products\EcoServices\Domain\Address\Models\ResidentAddress;
use App\Products\EcoServices\Domain\Notifications\Models\NotificationEvent;
use App\Products\EcoServices\Domain\Notifications\Models\NotificationTemplate;
use App\Products\EcoServices\Domain\Schedule\Models\CollectionScheduleDate;
use Illuminate\Support\Facades\Log;

class QueueCollectionReminderNotificationsAction
{
    /**
     * @return array{events:int}
     */
    public function execute(): array
    {
        Log::info('eco_services.notifications.collection_reminders.start');

        $events = 0;

        NotificationTemplate::query()
            ->where('trigger_type', 'collection_reminder')
            ->where('status', 'active')
            ->get()
            ->each(function (NotificationTemplate $template) use (&$events): void {
                $daysBefore = $template->days_before ?? 1;
                $targetDate = now()->addDays($daysBefore)->toDateString();

                CollectionScheduleDate::query()
                    ->with(['zone', 'fraction'])
                    ->whereDate('collection_date', $targetDate)
                    ->get()
                    ->each(function (CollectionScheduleDate $date) use ($template, &$events): void {
                        $events += $this->queueEventsForScheduleDate($template, $date);
                    });
            });

        Log::info('eco_services.notifications.collection_reminders.success', [
            'events' => $events,
        ]);

        return ['events' => $events];
    }

    private function queueEventsForScheduleDate(NotificationTemplate $template, CollectionScheduleDate $date): int
    {
        $events = 0;

        ResidentAddress::query()
            ->where('eco_zone_id', $date->eco_zone_id)
            ->where('is_active', true)
            ->get()
            ->each(function (ResidentAddress $address) use ($template, $date, &$events): void {
                $event = NotificationEvent::query()->firstOrCreate(
                    ['source_key' => $this->sourceKey($template, $date, $address)],
                    [
                        'eco_notification_template_id' => $template->id,
                        'event_type' => 'collection_reminder',
                        'audience_scope' => 'user:'.$address->user_id,
                        'payload' => [
                            'user_id' => $address->user_id,
                            'resident_address_id' => $address->id,
                            'schedule_date_id' => $date->id,
                            'collection_date' => $date->collection_date?->toDateString(),
                            'zone_name' => $date->zone?->name,
                            'fraction_name' => $date->fraction?->name,
                        ],
                    ],
                );

                if ($event->wasRecentlyCreated) {
                    $events++;
                }
            });

        return $events;
    }

    private function sourceKey(NotificationTemplate $template, CollectionScheduleDate $date, ResidentAddress $address): string
    {
        return 'collection_reminder:'.$template->id.':'.$date->id.':'.$address->id;
    }
}
