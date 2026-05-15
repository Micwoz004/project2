<?php

namespace App\Providers;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Voting\Models\VoteCard;
use App\Models\User;
use App\Policies\BudgetEditionPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ProjectAreaPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\VoteCardPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(BudgetEdition::class, BudgetEditionPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(ProjectArea::class, ProjectAreaPolicy::class);
        Gate::policy(VoteCard::class, VoteCardPolicy::class);

        Gate::define('view-results', fn (User $user) => $user->can('results.view') || $user->hasAnyRole(['admin', 'bdo']));
        Gate::define('export-reports', fn (User $user) => $user->can('reports.export') || $user->hasAnyRole(['admin', 'bdo']));
    }
}
