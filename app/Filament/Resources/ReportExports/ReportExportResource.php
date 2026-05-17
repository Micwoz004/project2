<?php

namespace App\Filament\Resources\ReportExports;

use App\Domain\Reports\Enums\AdminReportType;
use App\Domain\Reports\Enums\ReportExportFormat;
use App\Domain\Reports\Enums\ReportExportStatus;
use App\Domain\Reports\Models\ReportExport;
use App\Domain\Users\Enums\SystemPermission;
use App\Filament\Resources\ReportExports\Pages\ListReportExports;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ReportExportResource extends Resource
{
    protected static ?string $model = ReportExport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Raporty';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'file_name';

    public static function getModelLabel(): string
    {
        return 'eksport raportu';
    }

    public static function getPluralModelLabel(): string
    {
        return 'eksporty raportów';
    }

    public static function canViewAny(): bool
    {
        return self::canManageExports();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('report')
                    ->label('Raport')
                    ->formatStateUsing(fn (AdminReportType $state): string => $state->sheetName())
                    ->sortable(),
                TextColumn::make('format')
                    ->label('Format')
                    ->formatStateUsing(fn (ReportExportFormat $state): string => mb_strtoupper($state->value))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (ReportExportStatus $state): string => self::statusLabel($state))
                    ->badge()
                    ->sortable(),
                TextColumn::make('requestedBy.name')
                    ->label('Zlecający')
                    ->placeholder('System'),
                TextColumn::make('file_name')
                    ->label('Plik')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Zlecono')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Zakończono')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                Action::make('download')
                    ->label('Pobierz')
                    ->url(fn (ReportExport $record): string => route('admin.reports.exports.download', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (ReportExport $record): bool => $record->status === ReportExportStatus::Completed && $record->storage_path !== null),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReportExports::route('/'),
        ];
    }

    private static function statusLabel(ReportExportStatus $status): string
    {
        return match ($status) {
            ReportExportStatus::Queued => 'w kolejce',
            ReportExportStatus::Processing => 'w trakcie',
            ReportExportStatus::Completed => 'gotowy',
            ReportExportStatus::Failed => 'błąd',
        };
    }

    private static function canManageExports(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->can(SystemPermission::ReportsExport->value) || $user->hasAnyRole(['admin', 'bdo']));
    }
}
