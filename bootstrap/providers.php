<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\CivicBudgetPanelProvider;
use App\Providers\Filament\EcoServicesPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    CivicBudgetPanelProvider::class,
    EcoServicesPanelProvider::class,
];
