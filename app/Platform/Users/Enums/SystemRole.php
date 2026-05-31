<?php

namespace App\Platform\Users\Enums;

enum SystemRole: string
{
    case Admin = 'admin';
    case AnalystOds = 'analyst ODS';
    case Applicant = 'applicant';
    case CheckVoter = 'checkVoter';
    case Consultant = 'consultant';
    case Coordinator = 'coordinator';
    case ObserverZk = 'observer ZK';
    case ObserverZod = 'observer ZOD';
    case PresidentWJo = 'president W JO';
    case PresidentZk = 'president ZK';
    case PresidentZod = 'president ZOD';
    case VerifierWJo = 'verifier W JO';
    case VerifierZk = 'verifier ZK';
    case VerifierZod = 'verifier ZOD';
    case VicePresidentZk = 'vicepresident ZK';
    case VicePresidentZod = 'vicepresident ZOD';
    case Bdo = 'bdo';

    /**
     * @return list<string>
     */
    public static function legacyRoleNames(): array
    {
        return array_map(
            static fn (self $role): string => $role->value,
            self::cases(),
        );
    }

    /**
     * @return array<string, list<SystemPermission>>
     */
    public static function defaultPermissions(): array
    {
        return [
            self::Admin->value => SystemPermission::cases(),
            self::Bdo->value => [
                SystemPermission::PlatformAdminAccess,
                SystemPermission::PlatformUsersManage,
                SystemPermission::CivicBudgetAdminAccess,
                SystemPermission::CivicBudgetProjectsView,
                SystemPermission::CivicBudgetProjectsManage,
                SystemPermission::CivicBudgetVotingManage,
                SystemPermission::CivicBudgetResultsView,
                SystemPermission::CivicBudgetReportsExport,
                SystemPermission::CivicBudgetSettingsManage,
                SystemPermission::EcoUslugiAdminAccess,
                SystemPermission::EcoUslugiZonesManage,
                SystemPermission::EcoUslugiWasteManage,
                SystemPermission::EcoUslugiSchedulesManage,
                SystemPermission::EcoUslugiPszokManage,
                SystemPermission::EcoUslugiNewsManage,
                SystemPermission::EcoUslugiNotificationsManage,
                SystemPermission::EcoUslugiAirQualityManage,
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsManage,
                SystemPermission::ProjectCorrectionsManage,
                SystemPermission::BudgetEditionsManage,
                SystemPermission::DictionariesManage,
                SystemPermission::UsersManage,
                SystemPermission::VotingManage,
                SystemPermission::VoteCardsManage,
                SystemPermission::ResultsView,
                SystemPermission::ReportsExport,
                SystemPermission::SettingsManage,
            ],
            self::AnalystOds->value => [
                SystemPermission::CivicBudgetAdminAccess,
                SystemPermission::CivicBudgetProjectsView,
                SystemPermission::CivicBudgetResultsView,
                SystemPermission::CivicBudgetReportsExport,
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ResultsView,
                SystemPermission::ReportsExport,
            ],
            self::Applicant->value => [],
            self::CheckVoter->value => [
                SystemPermission::CivicBudgetAdminAccess,
                SystemPermission::CivicBudgetVotingManage,
                SystemPermission::AdminAccess,
                SystemPermission::VotingManage,
                SystemPermission::VoteCardsManage,
            ],
            self::Consultant->value => [
                SystemPermission::CivicBudgetAdminAccess,
                SystemPermission::CivicBudgetProjectsView,
                SystemPermission::CivicBudgetProjectsVerify,
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
            ],
            self::Coordinator->value => [
                SystemPermission::CivicBudgetAdminAccess,
                SystemPermission::CivicBudgetProjectsView,
                SystemPermission::CivicBudgetProjectsManage,
                SystemPermission::CivicBudgetProjectsVerify,
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsManage,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
                SystemPermission::ProjectCorrectionsManage,
            ],
            self::ObserverZk->value => [
                SystemPermission::CivicBudgetAdminAccess,
                SystemPermission::CivicBudgetProjectsView,
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
            ],
            self::ObserverZod->value => [
                SystemPermission::CivicBudgetAdminAccess,
                SystemPermission::CivicBudgetProjectsView,
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
            ],
            self::PresidentWJo->value => [
                SystemPermission::CivicBudgetAdminAccess,
                SystemPermission::CivicBudgetProjectsView,
                SystemPermission::CivicBudgetProjectsVerify,
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
            self::PresidentZk->value => [
                SystemPermission::CivicBudgetAdminAccess,
                SystemPermission::CivicBudgetProjectsView,
                SystemPermission::CivicBudgetProjectsVerify,
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
            self::PresidentZod->value => [
                SystemPermission::CivicBudgetAdminAccess,
                SystemPermission::CivicBudgetProjectsView,
                SystemPermission::CivicBudgetProjectsVerify,
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
            self::VerifierWJo->value => [
                SystemPermission::CivicBudgetAdminAccess,
                SystemPermission::CivicBudgetProjectsView,
                SystemPermission::CivicBudgetProjectsVerify,
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
            self::VerifierZk->value => [
                SystemPermission::CivicBudgetAdminAccess,
                SystemPermission::CivicBudgetProjectsView,
                SystemPermission::CivicBudgetProjectsVerify,
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
            self::VerifierZod->value => [
                SystemPermission::CivicBudgetAdminAccess,
                SystemPermission::CivicBudgetProjectsView,
                SystemPermission::CivicBudgetProjectsVerify,
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
            self::VicePresidentZk->value => [
                SystemPermission::CivicBudgetAdminAccess,
                SystemPermission::CivicBudgetProjectsView,
                SystemPermission::CivicBudgetProjectsVerify,
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
            self::VicePresidentZod->value => [
                SystemPermission::CivicBudgetAdminAccess,
                SystemPermission::CivicBudgetProjectsView,
                SystemPermission::CivicBudgetProjectsVerify,
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
        ];
    }
}
