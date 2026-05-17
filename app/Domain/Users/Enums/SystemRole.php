<?php

namespace App\Domain\Users\Enums;

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
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ResultsView,
                SystemPermission::ReportsExport,
            ],
            self::Applicant->value => [],
            self::CheckVoter->value => [
                SystemPermission::AdminAccess,
                SystemPermission::VotingManage,
                SystemPermission::VoteCardsManage,
            ],
            self::Consultant->value => [
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
            ],
            self::Coordinator->value => [
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsManage,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
                SystemPermission::ProjectCorrectionsManage,
            ],
            self::ObserverZk->value => [
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
            ],
            self::ObserverZod->value => [
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
            ],
            self::PresidentWJo->value => [
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
            self::PresidentZk->value => [
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
            self::PresidentZod->value => [
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
            self::VerifierWJo->value => [
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
            self::VerifierZk->value => [
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
            self::VerifierZod->value => [
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
            self::VicePresidentZk->value => [
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
            self::VicePresidentZod->value => [
                SystemPermission::AdminAccess,
                SystemPermission::ProjectsView,
                SystemPermission::ProjectsVerify,
                SystemPermission::FormalVerificationManage,
                SystemPermission::MeritVerificationManage,
            ],
        ];
    }
}
