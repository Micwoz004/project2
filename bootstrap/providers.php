<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\CivicBudgetPanelProvider;
use App\Providers\Filament\EkoUslugiPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    CivicBudgetPanelProvider::class,
    EkoUslugiPanelProvider::class,
];
