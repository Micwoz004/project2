<?php

use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminReportExportDownloadController;
use App\Http\Controllers\Public\PublicCoauthorConfirmationController;
use App\Http\Controllers\Public\PublicProjectCommentController;
use App\Http\Controllers\Public\PublicProjectController;
use App\Http\Controllers\Public\PublicProjectSubmissionCardController;
use App\Http\Controllers\Public\PublicResidentAccountController;
use App\Http\Controllers\Public\PublicResidentAuthController;
use App\Http\Controllers\Public\PublicResultsController;
use App\Http\Controllers\Public\PublicSpaController;
use App\Http\Controllers\Public\PublicVotingController;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicSpaController::class)->name('public.home');
Route::get('/login', PublicSpaController::class)->name('login');
Route::post('/login', [PublicResidentAuthController::class, 'login'])->middleware('throttle:5,1')->name('public.resident.login');
Route::get('/rejestracja', PublicSpaController::class)->middleware('guest')->name('register');
Route::post('/rejestracja', [PublicResidentAuthController::class, 'register'])->middleware(['guest', 'throttle:5,1'])->name('public.resident.register');
Route::get('/haslo/reset', PublicSpaController::class)->middleware('guest')->name('password.request');
Route::post('/haslo/reset', [PublicResidentAuthController::class, 'sendPasswordResetLink'])->middleware(['guest', 'throttle:5,1'])->name('password.email');
Route::get('/haslo/reset/{token}', PublicSpaController::class)->middleware('guest')->name('password.reset');
Route::post('/haslo/zmien', [PublicResidentAuthController::class, 'resetPassword'])->middleware(['guest', 'throttle:5,1'])->name('password.update');
Route::post('/logout', [PublicResidentAuthController::class, 'logout'])->middleware('auth')->name('public.resident.logout');
Route::get('/email/weryfikacja', PublicSpaController::class)->middleware('auth')->name('verification.notice');
Route::get('/email/weryfikacja/{id}/{hash}', [PublicResidentAuthController::class, 'verifyEmail'])->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');
Route::post('/email/weryfikacja/wyslij', [PublicResidentAuthController::class, 'resendEmailVerification'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');
Route::get('/ogloszenia', PublicSpaController::class)->name('public.announcements.index');
Route::get('/ogloszenia/{slug}', PublicSpaController::class)->name('public.announcements.show');
Route::get('/informacje/{slug}', PublicSpaController::class)->name('public.info.show');

Route::get('/projekty', PublicSpaController::class)->name('public.projects.index');
Route::get('/projekt/{project}', PublicSpaController::class)->name('public.projects.show');
Route::get('/projekty-mapa', PublicSpaController::class)->name('public.projects.map');
Route::get('/activation/confirmCocreator', PublicCoauthorConfirmationController::class)->name('public.coauthors.confirm');
Route::get('/projekty/zglos', PublicSpaController::class)->middleware(['auth', 'verified'])->name('public.projects.create');
Route::post('/projekty/zglos', [PublicProjectController::class, 'store'])->middleware(['auth', 'verified'])->name('public.projects.store');
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/panel', PublicSpaController::class)->name('public.resident.dashboard');
    Route::get('/moje-projekty', PublicSpaController::class)->name('public.resident.projects');
    Route::get('/moje-projekty/zglos', PublicSpaController::class)->name('public.resident.projects.create');
    Route::get('/moje-projekty/{project}/edycja', PublicSpaController::class)->name('public.resident.projects.edit');
    Route::put('/moje-projekty/{project}/edycja', [PublicProjectController::class, 'updateDraft'])->name('public.resident.projects.update');
    Route::get('/moje-projekty/{project}/karta-zgloszeniowa.pdf', PublicProjectSubmissionCardController::class)->name('public.resident.projects.submission-card');
    Route::get('/konto', PublicSpaController::class)->name('public.resident.account');
    Route::patch('/konto', PublicResidentAccountController::class)->name('public.resident.account.update');
    Route::post('/projekt/{project}/komentarze', [PublicProjectCommentController::class, 'store'])->name('public.projects.comments.store');
    Route::put('/projekt/{project}/komentarze/{comment}', [PublicProjectCommentController::class, 'update'])->name('public.projects.comments.update');
    Route::patch('/projekt/{project}/komentarze/{comment}/widocznosc', [PublicProjectCommentController::class, 'toggleHidden'])->name('public.projects.comments.toggle-hidden');
    Route::get('/moje-projekty/{project}/korekta', PublicSpaController::class)->name('public.projects.corrections.edit');
    Route::put('/moje-projekty/{project}/korekta', [PublicProjectController::class, 'updateCorrection'])->name('public.projects.corrections.update');
});
Route::get('/glosowanie', PublicSpaController::class)->name('public.voting.welcome');
Route::post('/glosowanie/kod-sms', [PublicVotingController::class, 'issueToken'])->name('public.voting.token');
Route::get('/voting/activateSession/{id}/{tokenStr}', [PublicVotingController::class, 'activateEmailToken'])->name('public.voting.email-token.activate');
Route::post('/glosowanie', [PublicVotingController::class, 'cast'])->name('public.voting.cast');
Route::get('/wyniki', PublicSpaController::class)->name('public.results.index');
Route::get('/wyniki/export.csv', [PublicResultsController::class, 'export'])->name('public.results.export');
Route::get('/raporty-publiczne', PublicSpaController::class)->name('public.reports.index');

Route::middleware('auth')->prefix('admin/reports')->name('admin.reports.')->group(function (): void {
    Route::get('/exports/{reportExport}/download', AdminReportExportDownloadController::class)->name('exports.download');
    Route::get('/vote-cards/{budgetEdition}.csv', [AdminReportController::class, 'voteCards'])->name('vote-cards');
    Route::get('/vote-cards/{budgetEdition}.xlsx', [AdminReportController::class, 'voteCardsXlsx'])->name('vote-cards.xlsx');
    Route::get('/submitted-projects.csv', [AdminReportController::class, 'submittedProjects'])->name('submitted-projects');
    Route::get('/submitted-projects.xlsx', [AdminReportController::class, 'submittedProjectsXlsx'])->name('submitted-projects.xlsx');
    Route::get('/unsent-advanced-verifications.csv', [AdminReportController::class, 'unsentAdvancedVerifications'])->name('unsent-advanced-verifications');
    Route::get('/unsent-advanced-verifications.xlsx', [AdminReportController::class, 'unsentAdvancedVerificationsXlsx'])->name('unsent-advanced-verifications.xlsx');
    Route::get('/project-corrections.csv', [AdminReportController::class, 'projectCorrections'])->name('project-corrections');
    Route::get('/project-corrections.xlsx', [AdminReportController::class, 'projectCorrectionsXlsx'])->name('project-corrections.xlsx');
    Route::get('/project-history.csv', [AdminReportController::class, 'projectHistory'])->name('project-history');
    Route::get('/project-history.xlsx', [AdminReportController::class, 'projectHistoryXlsx'])->name('project-history.xlsx');
    Route::get('/verification-manifest.csv', [AdminReportController::class, 'verificationManifest'])->name('verification-manifest');
    Route::get('/verification-manifest.xlsx', [AdminReportController::class, 'verificationManifestXlsx'])->name('verification-manifest.xlsx');
    Route::get('/category-comparison/{budgetEdition}.csv', [AdminReportController::class, 'categoryComparison'])->name('category-comparison');
    Route::get('/category-comparison/{budgetEdition}.xlsx', [AdminReportController::class, 'categoryComparisonXlsx'])->name('category-comparison.xlsx');
});
