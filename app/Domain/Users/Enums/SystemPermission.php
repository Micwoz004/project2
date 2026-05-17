<?php

namespace App\Domain\Users\Enums;

enum SystemPermission: string
{
    case AdminAccess = 'admin.access';
    case ProjectsView = 'projects.view';
    case ProjectsManage = 'projects.manage';
    case ProjectsVerify = 'projects.verify';
    case FormalVerificationManage = 'verification.formal.manage';
    case MeritVerificationManage = 'verification.merit.manage';
    case ProjectCorrectionsManage = 'project_corrections.manage';
    case BudgetEditionsManage = 'budget_editions.manage';
    case DictionariesManage = 'dictionaries.manage';
    case UsersManage = 'users.manage';
    case VotingManage = 'voting.manage';
    case VoteCardsManage = 'vote_cards.manage';
    case ResultsView = 'results.view';
    case ReportsExport = 'reports.export';
    case PeselManage = 'pesel.manage';
    case SettingsManage = 'settings.manage';

    /**
     * @return array<string, list<self>>
     */
    public static function legacyPermissionMap(): array
    {
        return [
            'assign coordinator' => [self::ProjectsManage, self::ProjectsVerify, self::MeritVerificationManage],
            'assign verifier' => [self::ProjectsManage, self::ProjectsVerify, self::MeritVerificationManage],
            'back rejected' => [self::ProjectsManage, self::ProjectsVerify],
            'generate documents' => [self::ReportsExport],
            'generate propose' => [self::ProjectsManage],
            'generate reports' => [self::ReportsExport, self::ResultsView],
            'manage pesel' => [self::PeselManage],
            'manage settings' => [self::SettingsManage, self::BudgetEditionsManage, self::DictionariesManage],
            'manage task groups' => [self::BudgetEditionsManage],
            'manage users' => [self::UsersManage],
            'manage votecards' => [self::VoteCardsManage, self::VotingManage],
            'propose task' => [self::ProjectsManage],
            'recommend W JO' => [self::ProjectsVerify, self::MeritVerificationManage],
            'update task' => [self::ProjectsManage, self::ProjectCorrectionsManage],
            'view tasks' => [self::ProjectsView],
        ];
    }
}
