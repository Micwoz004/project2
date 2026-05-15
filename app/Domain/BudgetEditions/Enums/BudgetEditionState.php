<?php

namespace App\Domain\BudgetEditions\Enums;

enum BudgetEditionState: string
{
    case Inactive = 'inactive';
    case Propose = 'propose';
    case PreVotingVerification = 'pre_voting_verification';
    case PreVotingCorrection = 'pre_voting_correction';
    case Voting = 'voting';
    case PostVotingVerification = 'post_voting_verification';
    case ResultAnnouncement = 'result_announcement';
}
