<?php

namespace App\Products\EcoServices\Filament\Resources\CollectionSchedules\Pages;

use App\Products\EcoServices\Domain\Schedule\Actions\ImportCollectionScheduleCsvAction;
use App\Products\EcoServices\Filament\Resources\CollectionSchedules\CollectionScheduleResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ListCollectionSchedules extends ListRecords
{
    protected static string $resource = CollectionScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importScheduleCsv')
                ->label('Import CSV')
                ->schema([
                    FileUpload::make('file')
                        ->label('Plik CSV')
                        ->disk('local')
                        ->directory('eco-services/schedule-imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->required(),
                    TextInput::make('name')
                        ->label('Nazwa harmonogramu')
                        ->placeholder('Harmonogram '.now()->year)
                        ->maxLength(180),
                    Toggle::make('replace')
                        ->label('Zastąp wcześniejsze terminy tego harmonogramu'),
                ])
                ->action(function (array $data, ImportCollectionScheduleCsvAction $importer): void {
                    $file = Arr::first((array) $data['file']);
                    $path = Storage::disk('local')->path((string) $file);
                    $scheduleName = (string) ($data['name'] ?: 'Harmonogram '.now()->year);
                    $stats = $importer->execute($path, $scheduleName, (bool) ($data['replace'] ?? false));

                    Notification::make()
                        ->title('Import harmonogramu zakończony')
                        ->body("Zaimportowano {$stats['imported']} terminów, pominięto {$stats['skipped']}.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
