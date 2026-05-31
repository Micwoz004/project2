<?php

use App\Http\Controllers\Api\Mobile\MobileProductController;
use App\Http\Controllers\Api\Mobile\MobileResidentAccountController;
use App\Http\Controllers\Api\Mobile\MobileResidentAuthController;
use App\Products\CivicBudget\Http\Controllers\Api\Mobile\MobileCivicBudgetController;
use App\Products\CivicBudget\Http\Controllers\Api\Mobile\MobileResidentProjectController;
use App\Products\CivicBudget\Http\Controllers\Api\Mobile\MobileVotingController;
use App\Products\EcoServices\Http\Controllers\Api\Mobile\MobileEcoServicesController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->name('mobile.')->group(function (): void {
    Route::get('/products', [MobileProductController::class, 'index'])->name('products.index');
    Route::post('/resident/login', [MobileResidentAuthController::class, 'login'])->middleware('throttle:5,1')->name('resident.login');
    Route::post('/resident/register', [MobileResidentAuthController::class, 'register'])->middleware('throttle:5,1')->name('resident.register');

    Route::prefix('civic-budget')
        ->middleware('product.enabled:civic_budget')
        ->name('civic-budget.')
        ->group(function (): void {
            Route::get('/overview', [MobileCivicBudgetController::class, 'overview'])->name('overview');
            Route::get('/editions/active', [MobileCivicBudgetController::class, 'activeEdition'])->name('editions.active');
            Route::get('/editions', [MobileCivicBudgetController::class, 'editions'])->name('editions.index');
            Route::get('/editions/{edition}', [MobileCivicBudgetController::class, 'edition'])->name('editions.show');
            Route::get('/editions/{edition}/settings', [MobileCivicBudgetController::class, 'settings'])->name('editions.settings');
            Route::get('/editions/{edition}/areas', [MobileCivicBudgetController::class, 'areas'])->name('editions.areas');
            Route::get('/editions/{edition}/categories', [MobileCivicBudgetController::class, 'categories'])->name('editions.categories');
            Route::get('/editions/{edition}/voting', [MobileVotingController::class, 'show'])->name('editions.voting.show');
            Route::get('/editions/{edition}/projects', [MobileCivicBudgetController::class, 'projects'])->name('projects.index');
            Route::get('/editions/{edition}/petitions', [MobileCivicBudgetController::class, 'publicPetitions'])->name('petitions.index');
            Route::get('/petitions/{project}', [MobileCivicBudgetController::class, 'publicPetition'])->name('petitions.show');
            Route::get('/projects/{project}', [MobileCivicBudgetController::class, 'project'])->name('projects.show');
            Route::post('/voting/token', [MobileVotingController::class, 'issueToken'])->middleware('throttle:5,1')->name('voting.token');
            Route::post('/voting/cast', [MobileVotingController::class, 'cast'])->middleware('throttle:10,1')->name('voting.cast');
        });

    Route::prefix('eco-services')
        ->middleware('product.enabled:eco_services')
        ->name('eco-services.')
        ->group(function (): void {
            Route::get('/overview', [MobileEcoServicesController::class, 'overview'])->name('overview');
            Route::get('/waste/fractions', [MobileEcoServicesController::class, 'fractions'])->name('waste.fractions');
            Route::get('/waste/search', [MobileEcoServicesController::class, 'searchWaste'])->name('waste.search');
            Route::post('/waste/recognize', [MobileEcoServicesController::class, 'recognizeWaste'])->middleware('auth.mobile')->name('waste.recognize');
            Route::get('/pszok', [MobileEcoServicesController::class, 'pszok'])->name('pszok.index');
            Route::get('/pszok/{point}', [MobileEcoServicesController::class, 'pszokPoint'])->name('pszok.show');
            Route::get('/air-quality', [MobileEcoServicesController::class, 'airQuality'])->name('air-quality.index');
            Route::get('/news', [MobileEcoServicesController::class, 'news'])->name('news.index');
            Route::get('/news/{post}', [MobileEcoServicesController::class, 'newsPost'])->name('news.show');

            Route::middleware('auth.mobile')->group(function (): void {
                Route::get('/resident/addresses', [MobileEcoServicesController::class, 'addresses'])->name('resident.addresses.index');
                Route::post('/resident/addresses', [MobileEcoServicesController::class, 'storeAddress'])->name('resident.addresses.store');
                Route::get('/resident/schedules/upcoming', [MobileEcoServicesController::class, 'upcoming'])->name('resident.schedules.upcoming');
            });
        });

    Route::middleware('auth.mobile')->group(function (): void {
        Route::get('/resident/me', [MobileResidentAuthController::class, 'me'])->name('resident.me');
        Route::post('/resident/logout', [MobileResidentAuthController::class, 'logout'])->name('resident.logout');
        Route::patch('/resident/account', [MobileResidentAccountController::class, 'update'])->name('resident.account.update');

        Route::get('/resident/projects', [MobileResidentProjectController::class, 'index'])->name('resident.projects.index');
        Route::post('/resident/projects', [MobileResidentProjectController::class, 'store'])->name('resident.projects.store');
        Route::put('/resident/projects/{project}', [MobileResidentProjectController::class, 'update'])->name('resident.projects.update');
        Route::put('/resident/projects/{project}/correction', [MobileResidentProjectController::class, 'updateCorrection'])->name('resident.projects.correction.update');
    });
});
