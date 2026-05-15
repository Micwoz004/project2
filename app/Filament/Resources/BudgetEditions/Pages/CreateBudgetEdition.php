<?php

namespace App\Filament\Resources\BudgetEditions\Pages;

use App\Domain\BudgetEditions\Actions\EnsureContentPagesForBudgetEditionAction;
use App\Domain\BudgetEditions\Services\BudgetEditionScheduleValidator;
use App\Filament\Resources\BudgetEditions\BudgetEditionResource;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateBudgetEdition extends CreateRecord
{
    protected static string $resource = BudgetEditionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            app(BudgetEditionScheduleValidator::class)->assertValid($data);
        } catch (DomainException $exception) {
            Notification::make()
                ->danger()
                ->title('Nie można zapisać edycji')
                ->body($exception->getMessage())
                ->send();

            throw new Halt;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        app(EnsureContentPagesForBudgetEditionAction::class)->execute($this->getRecord());
    }
}
