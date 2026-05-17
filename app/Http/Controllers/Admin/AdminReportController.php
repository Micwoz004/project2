<?php

namespace App\Http\Controllers\Admin;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Reports\Exports\AdminVoteCardsCsvExporter;
use App\Domain\Reports\Exports\CategoryComparisonCsvExporter;
use App\Domain\Reports\Exports\ProjectCorrectionsCsvExporter;
use App\Domain\Reports\Exports\ProjectHistoryCsvExporter;
use App\Domain\Reports\Exports\SubmittedProjectsCsvExporter;
use App\Domain\Reports\Exports\UnsentAdvancedVerificationsCsvExporter;
use App\Domain\Reports\Exports\VerificationResultManifestCsvExporter;
use App\Domain\Reports\Exports\XlsxFromCsvExporter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends Controller
{
    public function voteCards(BudgetEdition $budgetEdition, AdminVoteCardsCsvExporter $exporter): StreamedResponse
    {
        return $this->csvDownload(
            'admin_vote_cards',
            'karty-glosowania.csv',
            fn (): string => $exporter->export($budgetEdition),
            ['budget_edition_id' => $budgetEdition->id],
        );
    }

    public function voteCardsXlsx(BudgetEdition $budgetEdition, AdminVoteCardsCsvExporter $exporter, XlsxFromCsvExporter $xlsxExporter): StreamedResponse
    {
        return $this->xlsxDownload(
            'admin_vote_cards',
            'karty-glosowania.xlsx',
            'Karty głosowania',
            fn (): string => $exporter->export($budgetEdition),
            $xlsxExporter,
            ['budget_edition_id' => $budgetEdition->id],
        );
    }

    public function submittedProjects(SubmittedProjectsCsvExporter $exporter): StreamedResponse
    {
        return $this->csvDownload(
            'submitted_projects',
            'projekty-zlozone.csv',
            fn (): string => $exporter->export(),
        );
    }

    public function submittedProjectsXlsx(SubmittedProjectsCsvExporter $exporter, XlsxFromCsvExporter $xlsxExporter): StreamedResponse
    {
        return $this->xlsxDownload(
            'submitted_projects',
            'projekty-zlozone.xlsx',
            'Projekty złożone',
            fn (): string => $exporter->export(),
            $xlsxExporter,
        );
    }

    public function unsentAdvancedVerifications(UnsentAdvancedVerificationsCsvExporter $exporter): StreamedResponse
    {
        return $this->csvDownload(
            'unsent_advanced_verifications',
            'niewyslane-weryfikacje-jednostek.csv',
            fn (): string => $exporter->export(),
        );
    }

    public function unsentAdvancedVerificationsXlsx(UnsentAdvancedVerificationsCsvExporter $exporter, XlsxFromCsvExporter $xlsxExporter): StreamedResponse
    {
        return $this->xlsxDownload(
            'unsent_advanced_verifications',
            'niewyslane-weryfikacje-jednostek.xlsx',
            'Niewysłane weryfikacje',
            fn (): string => $exporter->export(),
            $xlsxExporter,
        );
    }

    public function projectCorrections(ProjectCorrectionsCsvExporter $exporter): StreamedResponse
    {
        return $this->csvDownload(
            'project_corrections',
            'korekty-projektow.csv',
            fn (): string => $exporter->export(),
        );
    }

    public function projectCorrectionsXlsx(ProjectCorrectionsCsvExporter $exporter, XlsxFromCsvExporter $xlsxExporter): StreamedResponse
    {
        return $this->xlsxDownload(
            'project_corrections',
            'korekty-projektow.xlsx',
            'Korekty projektów',
            fn (): string => $exporter->export(),
            $xlsxExporter,
        );
    }

    public function projectHistory(ProjectHistoryCsvExporter $exporter): StreamedResponse
    {
        return $this->csvDownload(
            'project_history',
            'historia-projektow.csv',
            fn (): string => $exporter->export(),
        );
    }

    public function projectHistoryXlsx(ProjectHistoryCsvExporter $exporter, XlsxFromCsvExporter $xlsxExporter): StreamedResponse
    {
        return $this->xlsxDownload(
            'project_history',
            'historia-projektow.xlsx',
            'Historia projektów',
            fn (): string => $exporter->export(),
            $xlsxExporter,
        );
    }

    public function verificationManifest(VerificationResultManifestCsvExporter $exporter): StreamedResponse
    {
        return $this->csvDownload(
            'verification_manifest',
            'manifest-wynikow-weryfikacji.csv',
            fn (): string => $exporter->export(),
        );
    }

    public function verificationManifestXlsx(VerificationResultManifestCsvExporter $exporter, XlsxFromCsvExporter $xlsxExporter): StreamedResponse
    {
        return $this->xlsxDownload(
            'verification_manifest',
            'manifest-wynikow-weryfikacji.xlsx',
            'Manifest weryfikacji',
            fn (): string => $exporter->export(),
            $xlsxExporter,
        );
    }

    public function categoryComparison(BudgetEdition $budgetEdition, CategoryComparisonCsvExporter $exporter): StreamedResponse
    {
        return $this->csvDownload(
            'category_comparison',
            'porownanie-kategorii.csv',
            fn (): string => $exporter->export($budgetEdition),
            ['budget_edition_id' => $budgetEdition->id],
        );
    }

    public function categoryComparisonXlsx(BudgetEdition $budgetEdition, CategoryComparisonCsvExporter $exporter, XlsxFromCsvExporter $xlsxExporter): StreamedResponse
    {
        return $this->xlsxDownload(
            'category_comparison',
            'porownanie-kategorii.xlsx',
            'Porównanie kategorii',
            fn (): string => $exporter->export($budgetEdition),
            $xlsxExporter,
            ['budget_edition_id' => $budgetEdition->id],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function csvDownload(string $report, string $fileName, callable $export, array $context = []): StreamedResponse
    {
        Gate::authorize('export-reports');

        Log::info('admin_report.download.start', [
            'report' => $report,
            'user_id' => Auth::id(),
            ...$context,
        ]);

        $csv = $export();

        Log::info('admin_report.download.success', [
            'report' => $report,
            'user_id' => Auth::id(),
            ...$context,
        ]);

        return response()->streamDownload(
            fn () => print $csv,
            $fileName,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function xlsxDownload(string $report, string $fileName, string $sheetName, callable $exportCsv, XlsxFromCsvExporter $xlsxExporter, array $context = []): StreamedResponse
    {
        Gate::authorize('export-reports');

        Log::info('admin_report.xlsx_download.start', [
            'report' => $report,
            'user_id' => Auth::id(),
            ...$context,
        ]);

        $xlsx = $xlsxExporter->export($exportCsv(), $sheetName);

        Log::info('admin_report.xlsx_download.success', [
            'report' => $report,
            'user_id' => Auth::id(),
            ...$context,
        ]);

        return response()->streamDownload(
            fn () => print $xlsx,
            $fileName,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }
}
