<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\CivicBudgetPanelProvider;
use App\Providers\Filament\EcoUslugiPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    CivicBudgetPanelProvider::class,
    EcoUslugiPanelProvider::class,
];
