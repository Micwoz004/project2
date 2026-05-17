<?php

namespace App\Domain\Communications\Enums;

enum LegacyCommunicationTrigger: string
{
    case TaskCorrespondence = 'task.correspondence';
    case TaskSubmitted = 'task.submitted';
    case TaskEditedPaper = 'task.edited_paper';
    case TaskAssignedVerifier = 'task.assigned_verifier';
    case TaskAssignedCoordinator = 'task.assigned_coordinator';
    case TaskBackToWorkingCopyEmail = 'task.back_to_working_copy.email';
    case TaskCallToCorrectionSms = 'task.call_to_correction.sms';
    case TaskStatusRejectedFormal = 'task.status.rejected_formal';
    case TaskStatusRejectedWjo = 'task.status.rejected_wjo';
    case TaskStatusRecommendedWjo = 'task.status.recommended_wjo';
    case TaskStatusPicked = 'task.status.picked';
    case FormalVerificationPositive = 'verification.formal.positive';
    case FormalVerificationNegative = 'verification.formal.negative';
    case WjoDepartmentAssigned = 'verification.wjo.department_assigned';
    case ZkRecommendedPositive = 'verification.zk.recommended_positive';
    case ZkRecommendedNegative = 'verification.zk.recommended_negative';
    case ZkManualAccepted = 'verification.zk.manual_accepted';
    case ZkManualRejected = 'verification.zk.manual_rejected';
    case VerificationPublishedAuthor = 'verification.published.author';
    case VerificationPublishedVerifier = 'verification.published.verifier';
    case VerificationDocumentWithAttachment = 'verification.document.with_attachment';
    case VerificationPressureAutomatic = 'verification.pressure.automatic';
    case VerificationPressureManual = 'verification.pressure.manual';
    case PublicCommentAdded = 'public_comment.added';
    case PublicCommentAdminHidden = 'public_comment.admin_hidden';
    case CocreatorConfirmation = 'cocreator.confirmation';
    case ProjectContactMessage = 'project.contact_message';
    case UserActivationEmail = 'user.activation.email';
    case UserActivationRepeatedEmail = 'user.activation.repeated_email';
    case UserPasswordResetEmail = 'user.password_reset.email';
    case VotingTokenEmail = 'voting.token.email';
    case VotingTokenSms = 'voting.token.sms';
    case VotingSummaryEmail = 'voting.summary.email';
    case VotingSummarySms = 'voting.summary.sms';
    case VotingSummarySmsFailureEmail = 'voting.summary.sms_failure_email';

    public function channel(): string
    {
        return str_ends_with($this->value, '.sms') || $this === self::VotingSummarySms
            ? 'sms'
            : 'mail';
    }

    public function legacySource(): string
    {
        return match ($this) {
            self::TaskCorrespondence => 'TaskController::actionView',
            self::TaskSubmitted => 'TaskController::actionUpdate',
            self::TaskEditedPaper => 'TaskController::actionUpdatePaper',
            self::TaskAssignedVerifier => 'TaskController::actionAssignVerifier',
            self::TaskAssignedCoordinator => 'TaskController::actionAssignCoordinator',
            self::TaskBackToWorkingCopyEmail, self::TaskCallToCorrectionSms => 'TaskController::actionCallToCorrection',
            self::TaskStatusRejectedFormal,
            self::TaskStatusRejectedWjo,
            self::TaskStatusRecommendedWjo,
            self::TaskStatusPicked => 'Task::sendStatusNotification',
            self::FormalVerificationPositive,
            self::FormalVerificationNegative => 'ProcessingController::actionFormalVerification',
            self::WjoDepartmentAssigned => 'ProcessingController::actionWjoDecree / actionAddAnotherDepartment',
            self::ZkRecommendedPositive,
            self::ZkRecommendedNegative => 'ProcessingController::actionSetZkResult',
            self::ZkManualAccepted,
            self::ZkManualRejected => 'ProcessingController::actionAcceptTask / actionRejectTask',
            self::VerificationPublishedAuthor => 'ProcessingController::sendPublishedNotificationToAuthor',
            self::VerificationPublishedVerifier => 'ProcessingController::sendPublishedNotificationToVerifiers',
            self::VerificationDocumentWithAttachment => 'DocumentController::sendNotificationEmailWithAttachment',
            self::VerificationPressureAutomatic => 'VerificationPressure::beforeValidate / VerificationPressure::send',
            self::VerificationPressureManual => 'VerificationPressureForm::sendManualPressure / VerificationPressure::send',
            self::PublicCommentAdded => 'CommentsController::actionAddComment',
            self::PublicCommentAdminHidden => 'CommentsController::actionToggleAdminHideComment',
            self::CocreatorConfirmation => 'Cocreator::sendConfirmation',
            self::ProjectContactMessage => 'TaskProposeController::actionContact / ContactForm::sendMessage',
            self::UserActivationEmail => 'UserController::actionRegister',
            self::UserActivationRepeatedEmail => 'ActivationController::actionRepeated',
            self::UserPasswordResetEmail => 'UserController::actionPasswordReset',
            self::VotingTokenEmail => 'VotingController::sendTokentByEmail',
            self::VotingTokenSms => 'VotingController::sendTokenBySms',
            self::VotingSummaryEmail => 'VotingController::sendVoteSummaryByEmail',
            self::VotingSummarySms => 'VotingController::sendVoteSummaryBySms',
            self::VotingSummarySmsFailureEmail => 'VotingController::sendVoteSummaryErrorEmail',
        };
    }

    /**
     * @return array<string, string>
     */
    public function settingsKeys(): array
    {
        return match ($this) {
            self::TaskCorrespondence => ['subject' => 'emailTitleTaskCorrespondence', 'body' => 'emailBodyTaskCorrespondence'],
            self::TaskSubmitted => ['subject' => 'emailTitleTaskProposeSubmit', 'body' => 'emailBodyTaskProposeSubmit'],
            self::TaskEditedPaper => ['subject' => 'emailTitleTaskEdited', 'body' => 'emailBodyTaskEdited'],
            self::TaskAssignedVerifier,
            self::TaskAssignedCoordinator => ['subject' => 'emailTitleTaskAssigned', 'body' => 'emailBodyTaskAssigned'],
            self::TaskBackToWorkingCopyEmail => ['subject' => 'emailTitleTaskBackToWorkingCopy', 'body' => 'emailBodyTaskBackToWorkingCopy'],
            self::TaskCallToCorrectionSms => ['body' => 'smsCallToCorrection'],
            self::TaskStatusRejectedFormal => ['subject' => 'emailTitleVerificationNegativeFormal', 'body' => 'emailBodyVerificationNegativeFormal'],
            self::TaskStatusRejectedWjo => ['subject' => 'emailTitleVerificationNegativeImpossible', 'body' => 'emailBodyVerificationNegativeImpossible'],
            self::TaskStatusRecommendedWjo => ['subject' => 'emailTitleVerificationContinued', 'body' => 'emailBodyVerificationContinued'],
            self::TaskStatusPicked => ['subject' => 'emailTitleVerificationPositive', 'body' => 'emailBodyVerificationPositive'],
            self::FormalVerificationPositive => ['subject' => 'emailTitleTaskFormallyVerifiedPositive', 'body' => 'emailBodyTaskFormallyVerifiedPositive'],
            self::FormalVerificationNegative => ['subject' => 'emailTitleTaskFormallyVerifiedNegative', 'body' => 'emailBodyTaskFormallyVerifiedNegative'],
            self::WjoDepartmentAssigned => ['subject' => 'emailTitleToWjoDepartments', 'body' => 'emailBodyToWjoDepartments'],
            self::ZkRecommendedPositive => ['subject' => 'emailTitleTaskRecommendedZo', 'body' => 'emailBodyTaskRecommendedZo'],
            self::ZkRecommendedNegative => ['subject' => 'emailTitleTaskRecommendedZoNegative', 'body' => 'emailBodyTaskRecommendedZoNegative'],
            self::VerificationPublishedAuthor => ['subject' => 'emailTitleTaskVerificationPublished', 'body' => 'emailBodyTaskVerificationPublished'],
            self::VerificationPublishedVerifier => ['subject' => 'emailTitleVerifierVerificationPublished', 'body' => 'emailBodyVerifierVerificationPublished'],
            self::VerificationPressureAutomatic => ['subject' => 'emailTitleTaskVerificationPressureType', 'body' => 'emailBodyTaskVerificationPressureType'],
            self::PublicCommentAdded => ['subject' => 'emailTitleCommentAdded', 'body' => 'emailBodyCommentAdded'],
            self::PublicCommentAdminHidden => ['subject' => 'emailTitleCommentAdminHidden', 'body' => 'emailBodyCommentAdminHidden'],
            self::CocreatorConfirmation => ['subject' => 'emailTitleConfirmCocreatorStatus', 'body' => 'emailBodyConfirmCocreatorStatus'],
            self::UserActivationEmail,
            self::UserActivationRepeatedEmail => ['subject' => 'emailTitleUserActivation', 'body' => 'emailBodyUserActivation'],
            self::UserPasswordResetEmail => ['subject' => 'emailTitleUserPasswordReset', 'body' => 'emailBodyUserPasswordReset'],
            self::VotingTokenSms => ['body' => 'smsVotingToken'],
            self::VotingSummarySms => ['body' => 'smsVotingSummary'],
            self::VotingTokenEmail => ['subject' => 'votingTokenEmailSubject', 'view' => 'votingToken'],
            self::VotingSummaryEmail => ['subject' => 'votingSummarySubject', 'view' => 'voteSummary'],
            self::ProjectContactMessage,
            self::VerificationDocumentWithAttachment,
            self::VerificationPressureManual,
            self::ZkManualAccepted,
            self::ZkManualRejected,
            self::VotingSummarySmsFailureEmail => [],
        };
    }

    public function recipient(): string
    {
        return match ($this) {
            self::TaskAssignedVerifier,
            self::TaskAssignedCoordinator,
            self::WjoDepartmentAssigned,
            self::VerificationPublishedVerifier,
            self::VerificationPressureAutomatic,
            self::VerificationPressureManual => 'operatorzy urzędu / jednostki',
            self::VotingSummarySmsFailureEmail => 'administrator techniczny',
            self::ProjectContactMessage,
            self::TaskCorrespondence,
            self::TaskSubmitted,
            self::TaskEditedPaper,
            self::TaskBackToWorkingCopyEmail,
            self::TaskCallToCorrectionSms,
            self::TaskStatusRejectedFormal,
            self::TaskStatusRejectedWjo,
            self::TaskStatusRecommendedWjo,
            self::TaskStatusPicked,
            self::FormalVerificationPositive,
            self::FormalVerificationNegative,
            self::ZkRecommendedPositive,
            self::ZkRecommendedNegative,
            self::ZkManualAccepted,
            self::ZkManualRejected,
            self::VerificationPublishedAuthor,
            self::VerificationDocumentWithAttachment,
            self::PublicCommentAdded => 'autor projektu',
            self::PublicCommentAdminHidden,
            self::CocreatorConfirmation,
            self::UserActivationEmail,
            self::UserActivationRepeatedEmail,
            self::UserPasswordResetEmail,
            self::VotingTokenEmail,
            self::VotingTokenSms,
            self::VotingSummaryEmail,
            self::VotingSummarySms => 'użytkownik publiczny',
        };
    }

    public function projectTemplate(): ?ProjectNotificationTemplate
    {
        return match ($this) {
            self::TaskCorrespondence => ProjectNotificationTemplate::CorrespondenceMessage,
            self::TaskBackToWorkingCopyEmail,
            self::TaskCallToCorrectionSms => ProjectNotificationTemplate::FormalCorrection,
            self::VerificationPressureAutomatic,
            self::VerificationPressureManual => ProjectNotificationTemplate::VerificationPressure,
            self::PublicCommentAdded => ProjectNotificationTemplate::PublicCommentAdded,
            self::PublicCommentAdminHidden => ProjectNotificationTemplate::PublicCommentAdminHidden,
            self::TaskStatusRejectedFormal,
            self::TaskStatusRejectedWjo,
            self::TaskStatusRecommendedWjo,
            self::TaskStatusPicked,
            self::FormalVerificationPositive,
            self::FormalVerificationNegative,
            self::ZkRecommendedPositive,
            self::ZkRecommendedNegative,
            self::ZkManualAccepted,
            self::ZkManualRejected,
            self::VerificationPublishedAuthor => ProjectNotificationTemplate::ProjectStatusChanged,
            default => null,
        };
    }
}
