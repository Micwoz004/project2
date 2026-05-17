<?php

namespace App\Domain\Reports\Services;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Reports\Enums\AdminReportType;
use App\Domain\Reports\Enums\ReportExportFormat;
use App\Domain\Reports\Exports\AdminVoteCardsCsvExporter;
use App\Domain\Reports\Exports\CategoryComparisonCsvExporter;
use App\Domain\Reports\Exports\ProjectCorrectionsCsvExporter;
use App\Domain\Reports\Exports\ProjectHistoryCsvExporter;
use App\Domain\Reports\Exports\SubmittedProjectsCsvExporter;
use App\Domain\Reports\Exports\UnsentAdvancedVerificationsCsvExporter;
use App\Domain\Reports\Exports\VerificationResultManifestCsvExporter;
use App\Domain\Reports\Exports\XlsxFromCsvExporter;
use DomainException;
use Illuminate\Support\Facades\Log;

class AdminReportExportGenerator
{
    public function __construct(
        private readonly AdminVoteCardsCsvExporter $adminVoteCardsCsvExporter,
        private readonly SubmittedProjectsCsvExporter $submittedProjectsCsvExporter,
        private readonly UnsentAdvancedVerificationsCsvExporter $unsentAdvancedVerificationsCsvExporter,
        private readonly ProjectCorrectionsCsvExporter $projectCorrectionsCsvExporter,
        private readonly ProjectHistoryCsvExporter $projectHistoryCsvExporter,
        private readonly VerificationResultManifestCsvExporter $verificationResultManifestCsvExporter,
        private readonly CategoryComparisonCsvExporter $categoryComparisonCsvExporter,
        private readonly XlsxFromCsvExporter $xlsxFromCsvExporter,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function generate(AdminReportType $report, ReportExportFormat $format, array $context = []): string
    {
        Log::info('admin_report_export.generate.start', [
            'report' => $report->value,
            'format' => $format->value,
        ]);

        $csv = match ($report) {
            AdminReportType::AdminVoteCards => $this->adminVoteCardsCsvExporter->export($this->budgetEdition($context)),
            AdminReportType::SubmittedProjects => $this->submittedProjectsCsvExporter->export(),
            AdminReportType::UnsentAdvancedVerifications => $this->unsentAdvancedVerificationsCsvExporter->export(),
            AdminReportType::ProjectCorrections => $this->projectCorrectionsCsvExporter->export(),
            AdminReportType::ProjectHistory => $this->projectHistoryCsvExporter->export(),
            AdminReportType::VerificationManifest => $this->verificationResultManifestCsvExporter->export(),
            AdminReportType::CategoryComparison => $this->categoryComparisonCsvExporter->export($this->budgetEdition($context)),
        };

        $content = $format === ReportExportFormat::Xlsx
            ? $this->xlsxFromCsvExporter->export($csv, $report->sheetName())
            : $csv;

        Log::info('admin_report_export.generate.success', [
            'report' => $report->value,
            'format' => $format->value,
        ]);

        return $content;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function budgetEdition(array $context): BudgetEdition
    {
        $budgetEditionId = (int) ($context['budget_edition_id'] ?? 0);

        if ($budgetEditionId < 1) {
            Log::warning('admin_report_export.generate.rejected_missing_budget_edition');

            throw new DomainException('Raport wymaga identyfikatora edycji SBO.');
        }

        return BudgetEdition::query()->findOrFail($budgetEditionId);
    }
}
