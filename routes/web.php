<?php

use App\Http\Controllers\Public\PublicProjectController;
use App\Http\Controllers\Public\PublicReportController;
use App\Http\Controllers\Public\PublicResultsController;
use App\Http\Controllers\Public\PublicVotingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/projekty');

Route::get('/projekty', [PublicProjectController::class, 'index'])->name('public.projects.index');
Route::get('/projekt/{project}', [PublicProjectController::class, 'show'])->name('public.projects.show');
Route::get('/projekty-mapa', [PublicProjectController::class, 'index'])->name('public.projects.map');
Route::get('/projekty/zglos', [PublicProjectController::class, 'create'])->name('public.projects.create');
Route::post('/projekty/zglos', [PublicProjectController::class, 'store'])->name('public.projects.store');
Route::get('/glosowanie', [PublicVotingController::class, 'welcome'])->name('public.voting.welcome');
Route::post('/glosowanie/kod-sms', [PublicVotingController::class, 'issueToken'])->name('public.voting.token');
Route::post('/glosowanie', [PublicVotingController::class, 'cast'])->name('public.voting.cast');
Route::get('/wyniki', [PublicResultsController::class, 'index'])->name('public.results.index');
Route::get('/wyniki/export.csv', [PublicResultsController::class, 'export'])->name('public.results.export');
Route::get('/raporty-publiczne', [PublicReportController::class, 'index'])->name('public.reports.index');
