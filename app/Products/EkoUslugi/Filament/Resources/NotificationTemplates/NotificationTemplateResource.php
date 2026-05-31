<?php

namespace App\Products\EkoUslugi\Filament\Resources\NotificationTemplates;

use App\Products\EkoUslugi\Domain\Notifications\Models\NotificationTemplate;
use App\Products\EkoUslugi\Filament\Resources\NotificationTemplates\Pages\CreateNotificationTemplate;
use App\Products\EkoUslugi\Filament\Resources\NotificationTemplates\Pages\EditNotificationTemplate;
use App\Products\EkoUslugi\Filament\Resources\NotificationTemplates\Pages\ListNotificationTemplates;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Eko usługi';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Definicja')
                ->schema([
                    TextInput::make('name')->label('Nazwa')->required()->maxLength(180),
                    Select::make('trigger_type')
                        ->label('Wyzwalacz')
                        ->options([
                            'schedule_reminder' => 'Przypomnienie o odbiorze',
                            'schedule_changed' => 'Zmiana harmonogramu',
                            'news_published' => 'Publikacja aktualności',
                        ])
                        ->required(),
                    Select::make('status')->label('Status')->options(['active' => 'Aktywny', 'inactive' => 'Nieaktywny'])->default('inactive')->required(),
                    TextInput::make('days_before')->label('Dni przed')->numeric(),
                ])
                ->columns(2),
            Section::make('Kanały')
                ->schema([
                    Toggle::make('email_enabled')->label('E-mail'),
                    TextInput::make('email_subject_template')->label('Temat e-mail')->maxLength(500),
                    Textarea::make('email_body_template')->label('Treść e-mail')->rows(6)->columnSpanFull(),
                    Toggle::make('sms_enabled')->label('SMS'),
                    Textarea::make('sms_body_template')->label('Treść SMS')->rows(3),
                    Toggle::make('push_enabled')->label('Push'),
                    Textarea::make('push_body_template')->label('Treść push')->rows(3),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nazwa')->searchable()->sortable(),
                TextColumn::make('trigger_type')->label('Wyzwalacz')->badge(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                IconColumn::make('email_enabled')->label('E-mail')->boolean(),
                IconColumn::make('sms_enabled')->label('SMS')->boolean(),
                IconColumn::make('push_enabled')->label('Push')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationTemplates::route('/'),
            'create' => CreateNotificationTemplate::route('/create'),
            'edit' => EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
