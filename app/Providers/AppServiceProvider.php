<?php

namespace App\Providers;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Users\Enums\SystemRole;
use App\Domain\Verification\Enums\BoardType;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Services\Sms\HttpSmsProvider;
use App\Domain\Voting\Services\Sms\NullSmsProvider;
use App\Domain\Voting\Services\Sms\SmsProvider;
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
        $this->app->bind(SmsProvider::class, function (): SmsProvider {
            return config('services.sms.driver') === 'http'
                ? new HttpSmsProvider
                : new NullSmsProvider;
        });
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
        Gate::define('cast-board-vote', fn (User $user, BoardType $boardType) => $this->canCastBoardVote($user, $boardType));
        Gate::define('manage-board-voting', fn (User $user) => $user->can('projects.manage') || $user->hasAnyRole(['admin', 'bdo']));
    }

    private function canCastBoardVote(User $user, BoardType $boardType): bool
    {
        if ($user->hasAnyRole(['admin', 'bdo'])) {
            return true;
        }

        return match ($boardType) {
            BoardType::Zk => $user->hasAnyRole([
                SystemRole::PresidentZk->value,
                SystemRole::VicePresidentZk->value,
                SystemRole::VerifierZk->value,
            ]),
            BoardType::Ot, BoardType::At => $user->hasAnyRole([
                SystemRole::PresidentZod->value,
                SystemRole::VicePresidentZod->value,
                SystemRole::VerifierZod->value,
            ]),
        };
    }
}
