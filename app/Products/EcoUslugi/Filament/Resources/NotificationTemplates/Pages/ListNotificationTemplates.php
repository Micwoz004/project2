<?php

namespace App\Products\EcoUslugi\Filament\Resources\NotificationTemplates\Pages;

use App\Products\EcoUslugi\Domain\Notifications\Actions\QueueCollectionReminderNotificationsAction;
use App\Products\EcoUslugi\Filament\Resources\NotificationTemplates\NotificationTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListNotificationTemplates extends ListRecords
{
    protected static string $resource = NotificationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('queueCollectionReminders')
                ->label('Utwórz przypomnienia')
                ->action(function (QueueCollectionReminderNotificationsAction $queueReminders): void {
                    $stats = $queueReminders->execute();

                    Notification::make()
                        ->title('Przypomnienia zostały przygotowane')
                        ->body("Utworzono {$stats['events']} zdarzeń powiadomień.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
