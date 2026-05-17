<?php

namespace App\Domain\Reports\Enums;

enum AdminReportType: string
{
    case AdminVoteCards = 'admin_vote_cards';
    case SubmittedProjects = 'submitted_projects';
    case UnsentAdvancedVerifications = 'unsent_advanced_verifications';
    case ProjectCorrections = 'project_corrections';
    case ProjectHistory = 'project_history';
    case VerificationManifest = 'verification_manifest';
    case CategoryComparison = 'category_comparison';

    public function requiresBudgetEdition(): bool
    {
        return in_array($this, [
            self::AdminVoteCards,
            self::CategoryComparison,
        ], true);
    }

    public function fileName(ReportExportFormat $format): string
    {
        $extension = $format->extension();

        return match ($this) {
            self::AdminVoteCards => "karty-glosowania.{$extension}",
            self::SubmittedProjects => "projekty-zlozone.{$extension}",
            self::UnsentAdvancedVerifications => "niewyslane-weryfikacje-jednostek.{$extension}",
            self::ProjectCorrections => "korekty-projektow.{$extension}",
            self::ProjectHistory => "historia-projektow.{$extension}",
            self::VerificationManifest => "manifest-wynikow-weryfikacji.{$extension}",
            self::CategoryComparison => "porownanie-kategorii.{$extension}",
        };
    }

    public function sheetName(): string
    {
        return match ($this) {
            self::AdminVoteCards => 'Karty głosowania',
            self::SubmittedProjects => 'Projekty złożone',
            self::UnsentAdvancedVerifications => 'Niewysłane weryfikacje',
            self::ProjectCorrections => 'Korekty projektów',
            self::ProjectHistory => 'Historia projektów',
            self::VerificationManifest => 'Manifest weryfikacji',
            self::CategoryComparison => 'Porównanie kategorii',
        };
    }
}
