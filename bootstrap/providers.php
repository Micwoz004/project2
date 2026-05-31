<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\CivicBudgetPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    CivicBudgetPanelProvider::class,
];
