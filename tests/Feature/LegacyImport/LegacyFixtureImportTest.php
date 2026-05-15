<?php

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Communications\Models\CorrespondenceMessage;
use App\Domain\Communications\Models\MailLog;
use App\Domain\Communications\Models\ProjectComment;
use App\Domain\Communications\Models\ProjectNotification;
use App\Domain\Communications\Models\ProjectPublicComment;
use App\Domain\Files\Models\ProjectFile;
use App\Domain\LegacyImport\Models\LegacyImportBatch;
use App\Domain\LegacyImport\Services\LegacyFixtureImportService;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Models\ProjectChangeSuggestion;
use App\Domain\Projects\Models\ProjectCoauthor;
use App\Domain\Projects\Models\ProjectCorrection;
use App\Domain\Projects\Models\ProjectCostItem;
use App\Domain\Projects\Models\ProjectVersion;
use App\Domain\Results\Services\ResultsCalculator;
use App\Domain\Settings\Models\ApplicationSetting;
use App\Domain\Settings\Models\ContentPage;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Enums\BoardType;
use App\Domain\Verification\Enums\VerificationAssignmentType;
use App\Domain\Verification\Models\BoardVoteRejection;
use App\Domain\Verification\Models\ConsultationVerification;
use App\Domain\Verification\Models\DetailedVerification;
use App\Domain\Verification\Models\FinalMeritVerification;
use App\Domain\Verification\Models\FormalVerification;
use App\Domain\Verification\Models\InitialMeritVerification;
use App\Domain\Verification\Models\LocationVerification;
use App\Domain\Verification\Models\ProjectBoardVote;
use App\Domain\Verification\Models\VerificationAssignment;
use App\Domain\Verification\Models\VerificationVersion;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Enums\VotingTokenType;
use App\Domain\Voting\Models\SmsLog;
use App\Domain\Voting\Models\Vote;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Models\VoterRegistryHash;
use App\Domain\Voting\Models\VotingToken;
use App\Models\User;

it('imports a legacy fixture with ids statuses relations and result totals', function (): void {
    $department = Department::query()->create([
        'legacy_id' => 500,
        'name' => 'Wydział testowy',
    ]);
    $boardUser = User::factory()->create([
        'legacy_id' => 600,
    ]);

    $payload = [
        'taskgroups' => [[
            'id' => 10,
            'name' => 'SBO 2025',
            'proposeStart' => '2025-01-01 00:00:00',
            'proposeEnd' => '2025-02-01 00:00:00',
            'preVotingVerificationEnd' => '2025-03-01 00:00:00',
            'votingStart' => '2025-04-01 00:00:00',
            'votingEnd' => '2025-04-15 23:59:59',
            'postVotingVerificationEnd' => '2025-05-01 00:00:00',
            'resultAnnouncementEnd' => '2025-06-01 00:00:00',
        ]],
        'settings' => [[
            'id' => 110,
            'category' => 'owner',
            'key' => 'websiteName',
            'value' => 's:37:"Szczeciński Budżet Obywatelski 2025";',
        ]],
        'pages' => [[
            'id' => 111,
            'taskGroupId' => 10,
            'symbol' => ContentPage::SYMBOL_WELCOME,
            'body' => '<p>Witaj w SBO 2025</p>',
        ]],
        'tasktypes' => [[
            'id' => 20,
            'name' => 'Pogodno',
            'symbol' => 'P1',
            'local' => true,
            'costLimit' => 1000000,
        ]],
        'categories' => [[
            'id' => 30,
            'name' => 'Zieleń',
        ], [
            'id' => 31,
            'name' => 'Sport',
        ]],
        'tasks' => [[
            'id' => 40,
            'taskGroupId' => 10,
            'taskTypeId' => 20,
            'categoryId' => 30,
            'number' => 7,
            'numberDrawn' => 11,
            'title' => 'Park kieszonkowy',
            'localization' => 'Szczecin Pogodno',
            'description' => 'Opis',
            'goal' => 'Cel',
            'argumentation' => 'Uzasadnienie',
            'status' => ProjectStatus::Picked->value,
            'cost' => '1000',
            'costFormatted' => 1000,
            'isSupportList' => true,
            'isPicked' => true,
        ]],
        'taskcosts' => [[
            'id' => 50,
            'taskId' => 40,
            'description' => 'Nasadzenia',
            'amount' => 1000,
        ]],
        'taskscategories' => [[
            'taskId' => 40,
            'categoryId' => 30,
        ], [
            'taskId' => 40,
            'categoryId' => 31,
        ]],
        'files' => [[
            'id' => 90,
            'taskId' => 40,
            'filename' => 'support.pdf',
            'originalName' => 'Lista poparcia.pdf',
            'type' => 1,
            'isTaskFormAttachment' => true,
        ]],
        'filesprivate' => [[
            'id' => 91,
            'taskId' => 40,
            'filename' => 'private.pdf',
            'originalName' => 'Prywatny.pdf',
            'type' => 3,
        ]],
        'cocreators' => [[
            'id' => 92,
            'taskId' => 40,
            'firstName' => 'Anna',
            'lastName' => 'Nowak',
            'email' => 'anna@example.test',
            'personalDataAgree' => true,
            'confirm' => true,
        ]],
        'taskverification' => [[
            'id' => 93,
            'taskId' => 40,
            'status' => 2,
            'result' => true,
            'resultComments' => 'Formalnie poprawny',
            'isPublic' => true,
            'answers' => ['hasSupportAttachment' => true],
        ]],
        'taskinitialmeritverification' => [[
            'id' => 94,
            'taskId' => 40,
            'departmentId' => 500,
            'status' => 2,
            'result' => true,
            'resultComments' => 'Wstępnie pozytywny',
        ]],
        'taskfinishmeritverification' => [[
            'id' => 95,
            'taskId' => 40,
            'departmentId' => 500,
            'status' => 2,
            'result' => true,
            'resultComments' => 'Końcowo pozytywny',
        ]],
        'taskconsultation' => [[
            'id' => 97,
            'taskId' => 40,
            'departmentId' => 500,
            'status' => 2,
            'result' => true,
            'resultComments' => 'Konsultacja pozytywna',
        ]],
        'detailedverification' => [[
            'id' => 116,
            'taskId' => 40,
            'isMoreThanDocumentation' => 1,
            'isCompleteInvestment' => 1,
            'isCompleteAnalysisNotRequired' => 3,
            'isNotBuildingProject' => 1,
            'isCompliantWithOldBudgets' => 1,
            'isCompliantWithCityPlans' => 1,
            'isNotPublicHelp' => 1,
            'isCompliantWithLaw' => 1,
            'isCompliantWithRules' => 1,
            'areCostsComplete' => 1,
            'isInCostLimit' => 1,
            'isAvailable' => 1,
            'hasRecommendations' => 1,
            'recommendations' => 'Zalecenia szczegółowe',
            'recommendationsDate' => '2025-03-11 10:00:00',
            'recommendationsForm' => 1,
            'verificationComments' => 'Uwagi szczegółowe',
            'verificationResult' => 1,
            'resultReason' => 'Pozytywnie',
            'verificationDate' => '2025-03-11 12:00:00',
            'modifyingUserId' => 600,
            'creatorId' => 600,
            'public' => 1,
        ]],
        'locationverification' => [[
            'id' => 117,
            'taskId' => 40,
            'isCompliantWithPlan' => 1,
            'isCompliantWithPlot' => 1,
            'isInvestmentNotPlanned' => 2,
            'isLawCompliant' => 1,
            'isPlotNotForSale' => 1,
            'hasRecommendations' => 0,
            'recommendations' => '',
            'recommendationsDate' => '0000-00-00 00:00:00',
            'recommendationsForm' => 1,
            'verificationComments' => 'Uwagi lokalizacyjne',
            'verificationResult' => 1,
            'resultReason' => 'Lokalizacja poprawna',
            'verificationDate' => '2025-03-11 13:00:00',
            'modifyingUserId' => 600,
            'creatorId' => 600,
            'public' => 0,
        ]],
        'verificationversion' => [[
            'id' => 118,
            'verificationId' => 116,
            'type' => 3,
            'userId' => 600,
            'data' => '{"verificationResult":"1","resultReason":"Pozytywnie"}',
            'createTime' => '2025-03-11 12:05:00',
        ]],
        'taskdepartmentassignment' => [[
            'id' => 96,
            'taskId' => 40,
            'departmentId' => 500,
            'type' => VerificationAssignmentType::MeritInitial->value,
            'deadline' => '2025-03-10 12:00:00',
        ]],
        'zkvotes' => [[
            'id' => 98,
            'taskId' => 40,
            'userId' => 600,
            'choice' => 1,
            'comment' => 'Za',
        ]],
        'atvotes' => [[
            'id' => 99,
            'taskId' => 40,
            'userId' => 600,
            'choice' => 2,
            'comment' => 'Do głosowania',
        ]],
        'otvotes' => [[
            'id' => 100,
            'taskId' => 40,
            'userId' => 600,
            'choice' => 4,
            'comment' => 'Akceptacja',
        ]],
        'atotvotesrejection' => [[
            'id' => 101,
            'taskId' => 40,
            'userId' => 600,
            'boardType' => BoardType::At->value,
            'comment' => 'Powód odrzucenia',
        ]],
        'correspondence' => [[
            'id' => 102,
            'taskId' => 40,
            'userId' => 600,
            'messageText' => 'Treść korespondencji',
            'isRead' => true,
            'readAt' => '2025-03-12 12:00:00',
        ]],
        'taskcomments' => [[
            'id' => 103,
            'taskId' => 40,
            'userId' => 600,
            'content' => 'Komentarz wewnętrzny',
        ]],
        'comments' => [[
            'id' => 114,
            'taskId' => 40,
            'parentId' => null,
            'creatorId' => 600,
            'content' => 'Publiczny komentarz',
            'hidden' => 0,
            'adminHidden' => 0,
            'moderated' => 1,
            'createTime' => '2025-04-05 10:00:00',
        ], [
            'id' => 115,
            'taskId' => 40,
            'parentId' => 114,
            'creatorId' => 600,
            'content' => 'Odpowiedź publiczna',
            'hidden' => 1,
            'adminHidden' => 0,
            'moderated' => 1,
            'createTime' => '2025-04-05 10:10:00',
        ]],
        'notification' => [[
            'id' => 112,
            'creatorId' => 600,
            'sentToUserId' => 600,
            'authorEmail' => 'sbo@example.test',
            'taskId' => 40,
            'notificationText' => 'Treść powiadomienia projektu',
            'sendDate' => '2025-03-14 10:00:00',
            'notificationSubject' => 'Powiadomienie SBO',
        ]],
        'maillogs' => [[
            'id' => 113,
            'createdByUserId' => 600,
            'email' => 'autor@example.test',
            'subject' => 'Mail testowy',
            'content' => 'Treść maila',
            'controller' => 'notification',
            'action' => 'send',
            'time' => '2025-03-14 10:01:00',
        ]],
        'taskcorrection' => [[
            'id' => 108,
            'taskId' => 40,
            'title' => 0,
            'taskTypeId' => 0,
            'localization' => 1,
            'mapData' => 1,
            'goal' => 0,
            'description' => 0,
            'argumentation' => 0,
            'recipients' => 0,
            'freeOfCharge' => 0,
            'cost' => 0,
            'supportAttachment' => 1,
            'agreementAttachment' => 0,
            'mapAttachment' => 0,
            'parentAgreementAttachment' => 0,
            'attachments' => 1,
            'availability' => 0,
            'categoryId' => 0,
            'notes' => 'Uzupełnić lokalizację i listę poparcia',
            'correctionDeadline' => '2025-03-20',
            'creatorId' => 600,
            'correctionDone' => 1,
            'createdAt' => '2025-03-12 14:00:00',
        ]],
        'taskchangessuggestion' => [[
            'id' => 109,
            'taskId' => 40,
            'oldData' => '{"title":"Stary tytuł","mapData":"[]"}',
            'oldCosts' => '[{"description":"Stary koszt","sum":"500"}]',
            'oldFiles' => '[{"id":"90","description":"Stary opis"}]',
            'newData' => '{"title":"Nowy tytuł","mapData":"[]"}',
            'newCosts' => '[{"description":"Nowy koszt","sum":"1000"}]',
            'newFiles' => '[{"id":"90","description":"Nowy opis"}]',
            'consultation' => 'Konsultacja zmian',
            'authorComment' => 'Akceptuję',
            'isAcceptedByAdmin' => 1,
            'createdAt' => '2025-03-12 15:00:00',
            'createdBy' => 600,
            'deadline' => '2025-03-20 23:59:59',
            'decision' => 2,
            'decisionBy' => 600,
            'decisionAt' => '2025-03-13 10:00:00',
        ]],
        'versions' => [[
            'id' => 104,
            'taskId' => 40,
            'userId' => 600,
            'status' => ProjectStatus::Picked->value,
            'data' => '{"title":"Park kieszonkowy","status":"10"}',
            'files' => '[{"id":"90","originalName":"Lista poparcia.pdf"}]',
            'costs' => '[{"id":"50","description":"Nasadzenia","sum":"1000"}]',
            'createTime' => '2025-03-12 13:00:00',
        ]],
        'newverification' => [[
            'id' => 105,
            'hash' => 'abcdef1234567890abcdef1234567890',
        ]],
        'votingtokens' => [[
            'id' => 106,
            'token' => '123456',
            'pesel' => '44051401458',
            'firstName' => 'Jan',
            'secondName' => '',
            'motherLastName' => 'Nowak',
            'lastName' => 'Kowalski',
            'fatherName' => '',
            'email' => '',
            'phone' => '500600700',
            'citizenConfirm' => 1,
            'livingAddress' => 'Szczecin',
            'schoolAddress' => '',
            'studyAddress' => '',
            'workAddress' => '',
            'parentName' => '',
            'parentConfirm' => 0,
            'statement' => 1,
            'cityStatement' => 1,
            'noPeselNumber' => 0,
            'disabled' => 0,
            'ip' => '127.0.0.1',
            'userAgent' => 'Feature test',
            'createTime' => '2025-04-01 10:00:00',
            'type' => VotingTokenType::Sms->value,
        ]],
        'voters' => [[
            'id' => 60,
            'pesel' => '44051401458',
            'firstName' => 'Jan',
            'secondName' => 'Piotr',
            'motherLastName' => 'Nowak',
            'lastName' => 'Kowalski',
            'fatherName' => 'Adam',
            'email' => 'jan@example.test',
            'street' => 'Jasne Błonia',
            'houseNo' => '1',
            'flatNo' => '2',
            'postCode' => '70-001',
            'city' => 'Szczecin',
            'created' => '2025-04-01 10:30:00',
            'ip' => '127.0.0.1',
            'birthDate' => '1944-05-14',
            'sex' => 'M',
            'age' => 80,
            'userAgent' => 'Feature test',
            'phone' => '500600700',
        ]],
        'smslogs' => [[
            'id' => 107,
            'phone' => '500600700',
            'ip' => '127.0.0.1',
            'voterId' => 60,
            'created' => '2025-04-01 10:00:05',
        ]],
        'votecards' => [[
            'id' => 70,
            'taskGroupId' => 10,
            'voterId' => 60,
            'creatorId' => 600,
            'consultantId' => 600,
            'checkoutUserId' => 600,
            'statement' => true,
            'termsAccepted' => true,
            'cityStatement' => true,
            'noPeselNumber' => false,
            'cardNo' => 101,
            'digital' => true,
            'status' => VoteCardStatus::Accepted->value,
            'checkoutDateTime' => '2025-04-02 12:00:00',
            'notes' => 'Karta testowa',
            'citizenConfirm' => 2,
            'livingAddress' => 'Szczecin',
            'schoolAddress' => '',
            'studyAddress' => '',
            'workAddress' => '',
            'parentName' => 'Jan Rodzic',
            'parentConfirm' => true,
            'ip' => '127.0.0.1',
            'created' => '2025-04-01 11:00:00',
            'modified' => '2025-04-02 12:00:00',
        ]],
        'votes' => [[
            'id' => 80,
            'voteCardId' => 70,
            'voterId' => 60,
            'taskId' => 40,
            'points' => 1,
        ]],
    ];

    $batch = app(LegacyFixtureImportService::class)->import($payload, 'unit-fixture');
    $edition = BudgetEdition::query()->where('legacy_id', 10)->firstOrFail();
    $project = Project::query()->where('legacy_id', 40)->firstOrFail();
    $voteCard = VoteCard::query()->where('legacy_id', 70)->firstOrFail();
    $totals = app(ResultsCalculator::class)->projectTotals($edition);

    expect($batch->source_path)->toBe('unit-fixture')
        ->and($batch->finished_at)->not->toBeNull()
        ->and($batch->stats['settings'])->toBe(1)
        ->and($batch->stats['pages'])->toBe(1)
        ->and($batch->stats['tasks'])->toBe(1)
        ->and($batch->stats['taskscategories'])->toBe(2)
        ->and($batch->stats['files'])->toBe(1)
        ->and($batch->stats['filesprivate'])->toBe(1)
        ->and($batch->stats['cocreators'])->toBe(1)
        ->and($batch->stats['taskverification'])->toBe(1)
        ->and($batch->stats['taskinitialmeritverification'])->toBe(1)
        ->and($batch->stats['taskfinishmeritverification'])->toBe(1)
        ->and($batch->stats['taskconsultation'])->toBe(1)
        ->and($batch->stats['detailedverification'])->toBe(1)
        ->and($batch->stats['locationverification'])->toBe(1)
        ->and($batch->stats['verificationversion'])->toBe(1)
        ->and($batch->stats['taskdepartmentassignment'])->toBe(1)
        ->and($batch->stats['zkvotes'])->toBe(1)
        ->and($batch->stats['atvotes'])->toBe(1)
        ->and($batch->stats['otvotes'])->toBe(1)
        ->and($batch->stats['atotvotesrejection'])->toBe(1)
        ->and($batch->stats['correspondence'])->toBe(1)
        ->and($batch->stats['taskcomments'])->toBe(1)
        ->and($batch->stats['comments'])->toBe(2)
        ->and($batch->stats['notification'])->toBe(1)
        ->and($batch->stats['maillogs'])->toBe(1)
        ->and($batch->stats['taskcorrection'])->toBe(1)
        ->and($batch->stats['taskchangessuggestion'])->toBe(1)
        ->and($batch->stats['versions'])->toBe(1)
        ->and($batch->stats['newverification'])->toBe(1)
        ->and($batch->stats['votingtokens'])->toBe(1)
        ->and($batch->stats['smslogs'])->toBe(1)
        ->and($edition->legacy_id)->toBe(10)
        ->and(ProjectArea::query()->where('legacy_id', 20)->firstOrFail()->is_local)->toBeTrue()
        ->and(ApplicationSetting::query()->where('legacy_id', 110)->firstOrFail()->value)
        ->toBe('s:37:"Szczeciński Budżet Obywatelski 2025";')
        ->and(ContentPage::query()->where('legacy_id', 111)->firstOrFail()->budget_edition_id)->toBe($edition->id)
        ->and(ContentPage::query()->where('legacy_id', 111)->firstOrFail()->body)->toBe('<p>Witaj w SBO 2025</p>')
        ->and($project->status)->toBe(ProjectStatus::Picked)
        ->and($project->budget_edition_id)->toBe($edition->id)
        ->and($project->categories()->pluck('categories.id')->sort()->values()->all())
        ->toBe(Category::query()->whereIn('legacy_id', [30, 31])->pluck('id')->sort()->values()->all())
        ->and(ProjectCostItem::query()->where('legacy_id', 50)->firstOrFail()->project_id)->toBe($project->id)
        ->and(ProjectFile::query()->where('legacy_id', 90)->firstOrFail()->is_private)->toBeFalse()
        ->and(ProjectFile::query()->where('legacy_id', 91)->firstOrFail()->is_private)->toBeTrue()
        ->and(ProjectCoauthor::query()->where('legacy_id', 92)->firstOrFail()->confirm)->toBeTrue()
        ->and(FormalVerification::query()->where('legacy_id', 93)->firstOrFail()->answers)->toBe(['hasSupportAttachment' => true])
        ->and(InitialMeritVerification::query()->where('legacy_id', 94)->firstOrFail()->department_id)->toBe($department->id)
        ->and(FinalMeritVerification::query()->where('legacy_id', 95)->firstOrFail()->result_comments)->toBe('Końcowo pozytywny')
        ->and(ConsultationVerification::query()->where('legacy_id', 97)->firstOrFail()->department_id)->toBe($department->id)
        ->and(DetailedVerification::query()->where('legacy_id', 116)->firstOrFail()->answers['isCompleteInvestment'])->toBe(1)
        ->and(DetailedVerification::query()->where('legacy_id', 116)->firstOrFail()->has_recommendations)->toBeTrue()
        ->and(LocationVerification::query()->where('legacy_id', 117)->firstOrFail()->answers['isInvestmentNotPlanned'])->toBe(2)
        ->and(LocationVerification::query()->where('legacy_id', 117)->firstOrFail()->recommendations_at)->toBeNull()
        ->and(VerificationVersion::query()->where('legacy_id', 118)->firstOrFail()->raw_data)
        ->toBe('{"verificationResult":"1","resultReason":"Pozytywnie"}')
        ->and(VerificationAssignment::query()->where('legacy_id', 96)->firstOrFail()->type)->toBe(VerificationAssignmentType::MeritInitial)
        ->and(ProjectBoardVote::query()->where('legacy_id', 98)->firstOrFail()->user_id)->toBe($boardUser->id)
        ->and(ProjectBoardVote::query()->where('legacy_id', 99)->firstOrFail()->board_type)->toBe(BoardType::At)
        ->and(ProjectBoardVote::query()->where('legacy_id', 100)->firstOrFail()->board_type)->toBe(BoardType::Ot)
        ->and(BoardVoteRejection::query()->where('legacy_id', 101)->firstOrFail()->comment)->toBe('Powód odrzucenia')
        ->and(CorrespondenceMessage::query()->where('legacy_id', 102)->firstOrFail()->is_read)->toBeTrue()
        ->and(ProjectComment::query()->where('legacy_id', 103)->firstOrFail()->content)->toBe('Komentarz wewnętrzny')
        ->and(ProjectPublicComment::query()->where('legacy_id', 114)->firstOrFail()->moderated)->toBeTrue()
        ->and(ProjectPublicComment::query()->where('legacy_id', 115)->firstOrFail()->parent_id)
        ->toBe(ProjectPublicComment::query()->where('legacy_id', 114)->firstOrFail()->id)
        ->and(ProjectPublicComment::query()->where('legacy_id', 115)->firstOrFail()->hidden)->toBeTrue()
        ->and(ProjectNotification::query()->where('legacy_id', 112)->firstOrFail()->subject)->toBe('Powiadomienie SBO')
        ->and(ProjectNotification::query()->where('legacy_id', 112)->firstOrFail()->created_by_id)->toBe($boardUser->id)
        ->and(MailLog::query()->where('legacy_id', 113)->firstOrFail()->controller)->toBe('notification')
        ->and(ProjectCorrection::query()->where('legacy_id', 108)->firstOrFail()->allowed_fields)->toBe([
            'localization',
            'map_data',
            'support_attachment',
            'attachments',
        ])
        ->and(ProjectCorrection::query()->where('legacy_id', 108)->firstOrFail()->correction_done)->toBeTrue()
        ->and(ProjectChangeSuggestion::query()->where('legacy_id', 109)->firstOrFail()->new_data['title'])->toBe('Nowy tytuł')
        ->and(ProjectChangeSuggestion::query()->where('legacy_id', 109)->firstOrFail()->new_costs[0]['sum'])->toBe('1000')
        ->and(ProjectChangeSuggestion::query()->where('legacy_id', 109)->firstOrFail()->is_accepted_by_admin)->toBeTrue()
        ->and(ProjectVersion::query()->where('legacy_id', 104)->firstOrFail()->data['title'])->toBe('Park kieszonkowy')
        ->and(ProjectVersion::query()->where('legacy_id', 104)->firstOrFail()->files[0]['originalName'])->toBe('Lista poparcia.pdf')
        ->and(ProjectVersion::query()->where('legacy_id', 104)->firstOrFail()->costs[0]['sum'])->toBe('1000')
        ->and(VoterRegistryHash::query()->where('legacy_id', 105)->firstOrFail()->hash)->toBe('ABCDEF1234567890ABCDEF1234567890')
        ->and(VotingToken::query()->where('legacy_id', 106)->firstOrFail()->type)->toBe(VotingTokenType::Sms)
        ->and(VotingToken::query()->where('legacy_id', 106)->firstOrFail()->extra_data['city_statement'])->toBeTrue()
        ->and($voteCard->voter->mother_last_name)->toBe('Nowak')
        ->and($voteCard->voter->phone)->toBe('500600700')
        ->and($voteCard->voter->city)->toBe('Szczecin')
        ->and(SmsLog::query()->where('legacy_id', 107)->firstOrFail()->voter_id)->toBe($voteCard->voter_id)
        ->and($voteCard->status)->toBe(VoteCardStatus::Accepted)
        ->and($voteCard->created_by_id)->toBe($boardUser->id)
        ->and($voteCard->consultant_id)->toBe($boardUser->id)
        ->and($voteCard->checkout_user_id)->toBe($boardUser->id)
        ->and($voteCard->terms_accepted)->toBeTrue()
        ->and($voteCard->city_statement)->toBeTrue()
        ->and($voteCard->parent_confirm)->toBeTrue()
        ->and($voteCard->parent_name)->toBe('Jan Rodzic')
        ->and($voteCard->ip)->toBe('127.0.0.1')
        ->and(Vote::query()->where('legacy_id', 80)->firstOrFail()->project_id)->toBe($project->id)
        ->and((int) $totals->first()->points)->toBe(1);
});

it('keeps fixture import idempotent by legacy ids', function (): void {
    $payload = [
        'taskgroups' => [[
            'id' => 10,
            'name' => 'SBO 2025',
            'proposeStart' => '2025-01-01 00:00:00',
            'proposeEnd' => '2025-02-01 00:00:00',
            'preVotingVerificationEnd' => '2025-03-01 00:00:00',
            'votingStart' => '2025-04-01 00:00:00',
            'votingEnd' => '2025-04-15 23:59:59',
            'postVotingVerificationEnd' => '2025-05-01 00:00:00',
            'resultAnnouncementEnd' => '2025-06-01 00:00:00',
        ]],
    ];

    app(LegacyFixtureImportService::class)->import($payload, 'first-pass');
    app(LegacyFixtureImportService::class)->import($payload, 'second-pass');

    expect(BudgetEdition::query()->where('legacy_id', 10)->count())->toBe(1)
        ->and(LegacyImportBatch::query()->count())->toBe(2);
});
