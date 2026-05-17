<?php

namespace App\Filament\Pages;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Models\Project;
use App\Domain\Reports\Actions\QueueAdminReportExportAction;
use App\Domain\Reports\Enums\AdminReportType;
use App\Domain\Reports\Enums\ReportExportFormat;
use App\Domain\Results\Actions\PublishResultSnapshotAction;
use App\Domain\Results\Actions\ResolveResultTieDecisionAction;
use App\Domain\Results\Services\ResultsDashboardService;
use App\Models\User;
use BackedEnum;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;
use UnitEnum;

class ResultsDashboard extends Page
{
    protected static ?string $slug = 'wyniki';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Wyniki';

    protected static string|UnitEnum|null $navigationGroup = 'Głosowanie';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.results-dashboard';

    public ?int $budgetEditionId = null;

    /**
     * @var array<int, string>
     */
    public array $editionOptions = [];

    /**
     * @var array<string, mixed>
     */
    public array $summary = [];

    /**
     * @var array<string, int|string|null>
     */
    public array $tieDecisionWinners = [];

    /**
     * @var array<string, string|null>
     */
    public array $tieDecisionNotes = [];

    public static function canAccess(): bool
    {
        return Auth::check() && Gate::allows('view-results');
    }

    public function getTitle(): string
    {
        return 'Wyniki i publikacja';
    }

    public function mount(): void
    {
        Log::info('admin_results_dashboard.view.start', [
            'user_id' => Auth::id(),
        ]);

        $this->editionOptions = $this->editionOptions();
        $this->budgetEditionId = request()->integer('budget_edition_id') ?: array_key_first($this->editionOptions);
        $this->loadDashboard();

        Log::info('admin_results_dashboard.view.success', [
            'user_id' => Auth::id(),
            'budget_edition_id' => $this->budgetEditionId,
        ]);
    }

    public function updatedBudgetEditionId(): void
    {
        $this->loadDashboard();
    }

    public function loadDashboard(): void
    {
        if ($this->budgetEditionId === null) {
            Log::info('admin_results_dashboard.empty', [
                'user_id' => Auth::id(),
            ]);

            $this->summary = [];

            return;
        }

        $edition = BudgetEdition::query()->find((int) $this->budgetEditionId);

        if (! $edition instanceof BudgetEdition) {
            Log::warning('admin_results_dashboard.rejected_missing_edition', [
                'user_id' => Auth::id(),
                'budget_edition_id' => $this->budgetEditionId,
            ]);

            $this->summary = [];

            return;
        }

        $this->summary = app(ResultsDashboardService::class)->summary($edition);
    }

    public function resolveTieDecision(string $formKey): void
    {
        Log::info('admin_results_dashboard.tie_decision.start', [
            'user_id' => Auth::id(),
            'budget_edition_id' => $this->budgetEditionId,
            'form_key' => $formKey,
        ]);

        $group = collect($this->summary['tie_groups'] ?? [])
            ->first(fn (array $tieGroup): bool => $tieGroup['form_key'] === $formKey);

        if ($group === null) {
            Log::warning('admin_results_dashboard.tie_decision.rejected_missing_group', [
                'user_id' => Auth::id(),
                'budget_edition_id' => $this->budgetEditionId,
                'form_key' => $formKey,
            ]);

            Notification::make()
                ->danger()
                ->title('Nie znaleziono aktualnej grupy remisowej.')
                ->send();

            return;
        }

        $winnerProjectId = (int) ($this->tieDecisionWinners[$formKey] ?? 0);

        if ($winnerProjectId <= 0) {
            Log::warning('admin_results_dashboard.tie_decision.rejected_missing_winner', [
                'user_id' => Auth::id(),
                'budget_edition_id' => $this->budgetEditionId,
                'form_key' => $formKey,
            ]);

            Notification::make()
                ->danger()
                ->title('Wybierz zwycięski projekt remisu.')
                ->send();

            return;
        }

        $operator = Auth::user();
        $edition = BudgetEdition::query()->find((int) $this->budgetEditionId);

        if (! $edition instanceof BudgetEdition || ! $operator instanceof User) {
            Log::warning('admin_results_dashboard.tie_decision.rejected_invalid_context', [
                'user_id' => Auth::id(),
                'budget_edition_id' => $this->budgetEditionId,
                'winner_project_id' => $winnerProjectId,
            ]);

            Notification::make()
                ->danger()
                ->title('Nie można zapisać decyzji dla aktualnych danych.')
                ->send();

            return;
        }

        $winner = Project::query()
            ->where('budget_edition_id', $edition->id)
            ->find($winnerProjectId);

        if (! $winner instanceof Project) {
            Log::warning('admin_results_dashboard.tie_decision.rejected_missing_winner', [
                'user_id' => Auth::id(),
                'budget_edition_id' => $edition->id,
                'winner_project_id' => $winnerProjectId,
            ]);

            Notification::make()
                ->danger()
                ->title('Nie znaleziono zwycięskiego projektu w tej edycji.')
                ->send();

            return;
        }

        try {
            app(ResolveResultTieDecisionAction::class)->execute(
                $edition,
                $group['project_ids'],
                $winner,
                $operator,
                $this->tieDecisionNotes[$formKey] ?? null,
            );
        } catch (DomainException $exception) {
            Log::warning('admin_results_dashboard.tie_decision.rejected_domain', [
                'user_id' => Auth::id(),
                'budget_edition_id' => $this->budgetEditionId,
                'form_key' => $formKey,
                'reason' => $exception->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();

            return;
        } catch (Throwable $exception) {
            Log::error('admin_results_dashboard.tie_decision.failed', [
                'user_id' => Auth::id(),
                'budget_edition_id' => $this->budgetEditionId,
                'form_key' => $formKey,
                'exception' => $exception,
            ]);

            throw $exception;
        }

        $this->loadDashboard();

        Notification::make()
            ->success()
            ->title('Decyzja remisu została zapisana.')
            ->send();

        Log::info('admin_results_dashboard.tie_decision.success', [
            'user_id' => Auth::id(),
            'budget_edition_id' => $this->budgetEditionId,
            'winner_project_id' => $winnerProjectId,
        ]);
    }

    public function publishResultSnapshot(): void
    {
        Log::info('admin_results_dashboard.publish_snapshot.start', [
            'user_id' => Auth::id(),
            'budget_edition_id' => $this->budgetEditionId,
        ]);

        $operator = Auth::user();
        $edition = BudgetEdition::query()->find((int) $this->budgetEditionId);

        if (! $edition instanceof BudgetEdition || ! $operator instanceof User) {
            Log::warning('admin_results_dashboard.publish_snapshot.rejected_invalid_context', [
                'user_id' => Auth::id(),
                'budget_edition_id' => $this->budgetEditionId,
            ]);

            Notification::make()
                ->danger()
                ->title('Nie można utrwalić wyników dla aktualnych danych.')
                ->send();

            return;
        }

        try {
            $publication = app(PublishResultSnapshotAction::class)->execute($edition, $operator);
        } catch (DomainException $exception) {
            Log::warning('admin_results_dashboard.publish_snapshot.rejected_domain', [
                'user_id' => Auth::id(),
                'budget_edition_id' => $this->budgetEditionId,
                'reason' => $exception->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();

            return;
        } catch (Throwable $exception) {
            Log::error('admin_results_dashboard.publish_snapshot.failed', [
                'user_id' => Auth::id(),
                'budget_edition_id' => $this->budgetEditionId,
                'exception' => $exception,
            ]);

            throw $exception;
        }

        $this->loadDashboard();

        Notification::make()
            ->success()
            ->title('Snapshot wyników został zapisany jako wersja '.$publication->version.'.')
            ->send();

        Log::info('admin_results_dashboard.publish_snapshot.success', [
            'user_id' => Auth::id(),
            'budget_edition_id' => $this->budgetEditionId,
            'result_publication_id' => $publication->id,
            'version' => $publication->version,
        ]);
    }

    public function queueReportExport(string $report, string $format): void
    {
        Log::info('admin_results_dashboard.report_export.queue.start', [
            'user_id' => Auth::id(),
            'budget_edition_id' => $this->budgetEditionId,
            'report' => $report,
            'format' => $format,
        ]);

        $operator = Auth::user();
        $reportType = AdminReportType::tryFrom($report);
        $exportFormat = ReportExportFormat::tryFrom($format);

        if (! $operator instanceof User || ! $reportType instanceof AdminReportType || ! $exportFormat instanceof ReportExportFormat) {
            Log::warning('admin_results_dashboard.report_export.queue.rejected_invalid_context', [
                'user_id' => Auth::id(),
                'budget_edition_id' => $this->budgetEditionId,
                'report' => $report,
                'format' => $format,
            ]);

            Notification::make()
                ->danger()
                ->title('Nie można zlecić eksportu dla aktualnych danych.')
                ->send();

            return;
        }

        $context = [];

        if ($reportType->requiresBudgetEdition()) {
            if ($this->budgetEditionId === null) {
                Log::warning('admin_results_dashboard.report_export.queue.rejected_missing_edition', [
                    'user_id' => Auth::id(),
                    'report' => $reportType->value,
                ]);

                Notification::make()
                    ->danger()
                    ->title('Ten raport wymaga wybranej edycji SBO.')
                    ->send();

                return;
            }

            $context['budget_edition_id'] = (int) $this->budgetEditionId;
        }

        try {
            $export = app(QueueAdminReportExportAction::class)->execute($operator, $reportType, $exportFormat, $context);
        } catch (DomainException $exception) {
            Log::warning('admin_results_dashboard.report_export.queue.rejected_domain', [
                'user_id' => Auth::id(),
                'budget_edition_id' => $this->budgetEditionId,
                'report' => $reportType->value,
                'format' => $exportFormat->value,
                'reason' => $exception->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();

            return;
        } catch (Throwable $exception) {
            Log::error('admin_results_dashboard.report_export.queue.failed', [
                'user_id' => Auth::id(),
                'budget_edition_id' => $this->budgetEditionId,
                'report' => $reportType->value,
                'format' => $exportFormat->value,
                'exception' => $exception,
            ]);

            throw $exception;
        }

        Notification::make()
            ->success()
            ->title('Eksport #'.$export->id.' został zlecony.')
            ->send();

        Log::info('admin_results_dashboard.report_export.queue.success', [
            'user_id' => Auth::id(),
            'budget_edition_id' => $this->budgetEditionId,
            'report_export_id' => $export->id,
            'report' => $reportType->value,
            'format' => $exportFormat->value,
        ]);
    }

    public function canResolveResultTies(): bool
    {
        $user = Auth::user();

        return $user instanceof User && ($user->can('reports.export') || $user->hasAnyRole(['admin', 'bdo']));
    }

    public function canPublishResultSnapshot(): bool
    {
        return $this->canResolveResultTies();
    }

    /**
     * @return array<int, string>
     */
    private function editionOptions(): array
    {
        return BudgetEdition::query()
            ->orderByDesc('result_announcement_end')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(fn (BudgetEdition $edition): array => [
                $edition->id => 'Edycja #'.$edition->id.' - '.$edition->voting_start?->format('Y-m-d'),
            ])
            ->all();
    }
}
