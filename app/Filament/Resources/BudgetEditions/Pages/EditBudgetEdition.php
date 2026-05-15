<?php

namespace App\Filament\Resources\BudgetEditions\Pages;

use App\Domain\BudgetEditions\Actions\EnsureContentPagesForBudgetEditionAction;
use App\Domain\BudgetEditions\Services\BudgetEditionScheduleValidator;
use App\Filament\Resources\BudgetEditions\BudgetEditionResource;
use DomainException;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;

class EditBudgetEdition extends EditRecord
{
    protected static string $resource = BudgetEditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        try {
            app(BudgetEditionScheduleValidator::class)->assertValid($data, $this->getRecord()->id);
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

    protected function afterSave(): void
    {
        app(EnsureContentPagesForBudgetEditionAction::class)->execute($this->getRecord());
    }
}
