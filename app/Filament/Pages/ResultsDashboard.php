<?php

namespace App\Filament\Pages;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Results\Services\ResultsDashboardService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
