<?php

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Projects\Models\ProjectCorrection;
use App\Domain\Projects\Models\ProjectVersion;
use App\Domain\Reports\Exports\AdminVoteCardsCsvExporter;
use App\Domain\Reports\Exports\ProjectCorrectionsCsvExporter;
use App\Domain\Reports\Exports\ProjectHistoryCsvExporter;
use App\Domain\Reports\Exports\SubmittedProjectsCsvExporter;
use App\Domain\Reports\Exports\UnsentAdvancedVerificationsCsvExporter;
use App\Domain\Reports\Services\ProjectReportService;
use App\Domain\Reports\Services\VoteCardReportService;
use App\Domain\Results\Services\ResultsCalculator;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Domain\Users\Models\Department;
use App\Domain\Verification\Models\AdvancedVerification;
use App\Domain\Voting\Actions\RegisterPaperVoteCardAction;
use App\Domain\Voting\Actions\UpdateVoteCardStatusAction;
use App\Domain\Voting\Data\VoterIdentityData;
use App\Domain\Voting\Enums\CitizenConfirmation;
use App\Domain\Voting\Enums\VoteCardStatus;
use App\Domain\Voting\Models\VoteCard;
use App\Domain\Voting\Models\Voter;
use App\Domain\Voting\Models\VoterRegistryHash;
use App\Domain\Voting\Models\VotingToken;
use App\Domain\Voting\Services\CastVoteService;
use App\Domain\Voting\Services\VoterHashService;
use App\Domain\Voting\Services\VotingTokenService;
use App\Models\User;

it('casts a vote transactionally and blocks duplicate pesel', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $localArea = ProjectArea::query()->create(areaAttributes());
    $citywideArea = ProjectArea::query()->create(areaAttributes([
        'name' => 'Ogólnomiejskie',
        'symbol' => 'OGM',
        'is_local' => false,
    ]));
    $localProject = Project::query()->create(projectAttributes($edition->id, $localArea->id, [
        'status' => ProjectStatus::Picked,
    ]));
    $citywideProject = Project::query()->create(projectAttributes($edition->id, $citywideArea->id, [
        'title' => 'Projekt ogólnomiejski',
        'status' => ProjectStatus::Picked,
    ]));

    $identity = new VoterIdentityData(
        pesel: '44051401458',
        firstName: 'Jan',
        lastName: 'Kowalski',
        motherLastName: 'Nowak',
    );

    $voteCard = app(CastVoteService::class)->cast(
        $edition,
        $identity,
        [$localProject->id],
        [$citywideProject->id],
        ['citizen_confirm' => CitizenConfirmation::Living],
    );

    expect($voteCard->status)->toBe(VoteCardStatus::Accepted)
        ->and($voteCard->votes()->count())->toBe(2);

    app(CastVoteService::class)->cast(
        $edition,
        $identity,
        [],
        [$citywideProject->id],
        [
            'citizen_confirm' => CitizenConfirmation::Living,
            'confirm_missing_category' => true,
        ],
    );
})->throws(DomainException::class, 'Podany PESEL brał już udział w głosowaniu.');

it('accepts voter confirmed by legacy registry hash without declaration', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));
    $identity = new VoterIdentityData(
        pesel: '44051401458',
        firstName: 'Jan',
        lastName: 'Kowalski',
        motherLastName: 'Nowak',
    );

    VoterRegistryHash::query()->create([
        'hash' => app(VoterHashService::class)->legacyLookupHash(
            $identity->pesel,
            $identity->firstName,
            $identity->lastName,
            $identity->motherLastName,
        ),
    ]);

    $voteCard = app(CastVoteService::class)->cast(
        $edition,
        $identity,
        [$project->id],
        [],
        ['confirm_missing_category' => true],
    );

    expect($voteCard->status)->toBe(VoteCardStatus::Accepted);
});

it('requires registry match or citizen declaration for voters with pesel', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));
    $identity = new VoterIdentityData(
        pesel: '44051401458',
        firstName: 'Jan',
        lastName: 'Kowalski',
        motherLastName: 'Nowak',
    );

    app(CastVoteService::class)->cast(
        $edition,
        $identity,
        [$project->id],
        [],
        ['confirm_missing_category' => true],
    );
})->throws(DomainException::class, 'Wyborca musi być potwierdzony w rejestrze albo złożyć oświadczenie.');

it('requires parent consent for minors', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));
    $identity = new VoterIdentityData(
        pesel: '10251500004',
        firstName: 'Jan',
        lastName: 'Kowalski',
        motherLastName: 'Nowak',
    );

    app(CastVoteService::class)->cast(
        $edition,
        $identity,
        [$project->id],
        [],
        [
            'citizen_confirm' => CitizenConfirmation::Living,
            'confirm_missing_category' => true,
        ],
    );
})->throws(DomainException::class, 'Wyborca niepełnoletni wymaga zgody rodzica lub opiekuna.');

it('stores no-pesel vote cards as verifying', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));
    $identity = new VoterIdentityData(
        pesel: '',
        firstName: 'Anna',
        lastName: 'Nowak',
        motherLastName: '',
        noPeselNumber: true,
    );

    $voteCard = app(CastVoteService::class)->cast(
        $edition,
        $identity,
        [$project->id],
        [],
        ['confirm_missing_category' => true],
    );

    expect($voteCard->status)->toBe(VoteCardStatus::Verifying)
        ->and($voteCard->no_pesel_number)->toBeTrue()
        ->and($voteCard->voter->pesel)->toBeNull();
});

it('requires explicit confirmation when voter skips one category', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));
    $identity = new VoterIdentityData(
        pesel: '44051401458',
        firstName: 'Jan',
        lastName: 'Kowalski',
        motherLastName: 'Nowak',
    );

    app(CastVoteService::class)->cast(
        $edition,
        $identity,
        [$project->id],
        [],
        ['citizen_confirm' => CitizenConfirmation::Living],
    );
})->throws(DomainException::class, 'Brak głosu w jednej kategorii wymaga potwierdzenia.');

it('issues six digit sms token and disables previous tokens for pesel', function (): void {
    $identity = new VoterIdentityData(
        pesel: '44051401458',
        firstName: 'Jan',
        lastName: 'Kowalski',
        motherLastName: 'Nowak',
    );

    $first = app(VotingTokenService::class)->issueSmsToken($identity, '500600700');
    $second = app(VotingTokenService::class)->issueSmsToken($identity, '500600701');

    expect($first->refresh()->disabled)->toBeTrue()
        ->and($second->token)->toMatch('/^[0-9]{6}$/')
        ->and($second->disabled)->toBeFalse();
});

it('activates sms token by phone and code', function (): void {
    $identity = new VoterIdentityData(
        pesel: '44051401458',
        firstName: 'Jan',
        lastName: 'Kowalski',
        motherLastName: 'Nowak',
    );
    $token = app(VotingTokenService::class)->issueSmsToken($identity, '500600700');

    $activated = app(VotingTokenService::class)->activateSmsToken('500600700', $token->token);

    expect($activated->id)->toBe($token->id)
        ->and($activated->disabled)->toBeFalse();
});

it('rejects invalid sms token activation', function (): void {
    app(VotingTokenService::class)->activateSmsToken('500600700', '000000');
})->throws(DomainException::class, 'Kod dostępu jest nieprawidłowy.');

it('disables active sms token after successful vote cast', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));
    $identity = new VoterIdentityData(
        pesel: '44051401458',
        firstName: 'Jan',
        lastName: 'Kowalski',
        motherLastName: 'Nowak',
    );
    $token = app(VotingTokenService::class)->issueSmsToken($identity, '500600700');
    $activated = app(VotingTokenService::class)->activateSmsToken('500600700', $token->token);

    app(CastVoteService::class)->cast(
        $edition,
        $identity,
        [$project->id],
        [],
        [
            'citizen_confirm' => CitizenConfirmation::Living,
            'confirm_missing_category' => true,
            'voting_token' => $activated,
        ],
    );

    expect($token->refresh()->disabled)->toBeTrue();
});

it('limits sms tokens to five per phone', function (): void {
    foreach (range(1, 5) as $index) {
        $identity = new VoterIdentityData(
            pesel: "4405140145$index",
            firstName: 'Jan',
            lastName: 'Kowalski',
            motherLastName: 'Nowak',
        );

        VotingToken::query()->create([
            'token' => (string) (100000 + $index),
            'pesel' => $identity->pesel,
            'first_name' => $identity->firstName,
            'mother_last_name' => $identity->motherLastName,
            'last_name' => $identity->lastName,
            'phone' => '500600700',
            'disabled' => false,
            'type' => 2,
        ]);
    }

    $identity = new VoterIdentityData(
        pesel: '44051401458',
        firstName: 'Jan',
        lastName: 'Kowalski',
        motherLastName: 'Nowak',
    );

    app(VotingTokenService::class)->issueSmsToken($identity, '500600700');
})->throws(DomainException::class, 'Przekroczono limit kodów SMS dla numeru telefonu.');

it('counts only accepted vote cards in public results', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));

    $acceptedVoter = Voter::query()->create([
        'pesel' => '44051401458',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
    ]);
    $acceptedCard = VoteCard::query()->create([
        'budget_edition_id' => $edition->id,
        'voter_id' => $acceptedVoter->id,
        'status' => VoteCardStatus::Accepted,
    ]);
    $acceptedCard->votes()->create([
        'voter_id' => $acceptedVoter->id,
        'project_id' => $project->id,
        'points' => 1,
    ]);

    $rejectedVoter = Voter::query()->create([
        'pesel' => '02270803628',
        'first_name' => 'Anna',
        'last_name' => 'Nowak',
    ]);
    $rejectedCard = VoteCard::query()->create([
        'budget_edition_id' => $edition->id,
        'voter_id' => $rejectedVoter->id,
        'status' => VoteCardStatus::Rejected,
    ]);
    $rejectedCard->votes()->create([
        'voter_id' => $rejectedVoter->id,
        'project_id' => $project->id,
        'points' => 1,
    ]);

    $totals = app(ResultsCalculator::class)->projectTotals($edition);

    expect($totals)->toHaveCount(1)
        ->and((int) $totals->first()->points)->toBe(1);
});

it('orders project totals by points then drawn number for deterministic ties', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $firstProject = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'number_drawn' => 2,
        'status' => ProjectStatus::Picked,
    ]));
    $secondProject = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'number_drawn' => 1,
        'title' => 'Drugi projekt',
        'status' => ProjectStatus::Picked,
    ]));

    foreach ([$firstProject, $secondProject] as $index => $project) {
        $voter = Voter::query()->create([
            'pesel' => '4405140145'.$index,
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
        ]);
        $voteCard = VoteCard::query()->create([
            'budget_edition_id' => $edition->id,
            'voter_id' => $voter->id,
            'status' => VoteCardStatus::Accepted,
        ]);
        $voteCard->votes()->create([
            'voter_id' => $voter->id,
            'project_id' => $project->id,
            'points' => 1,
        ]);
    }

    $totals = app(ResultsCalculator::class)->projectTotals($edition);

    expect($totals->pluck('project_id')->all())->toBe([
        $secondProject->id,
        $firstProject->id,
    ]);
});

it('updates vote card status administratively and recalculates results', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));
    $operator = User::factory()->create();
    $voter = Voter::query()->create([
        'pesel' => '44051401458',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
    ]);
    $voteCard = VoteCard::query()->create([
        'budget_edition_id' => $edition->id,
        'voter_id' => $voter->id,
        'status' => VoteCardStatus::Verifying,
    ]);
    $voteCard->votes()->create([
        'voter_id' => $voter->id,
        'project_id' => $project->id,
        'points' => 1,
    ]);

    $before = app(ResultsCalculator::class)->projectTotals($edition);

    $updated = app(UpdateVoteCardStatusAction::class)->execute(
        $voteCard,
        VoteCardStatus::Accepted,
        $operator,
        'Po weryfikacji ręcznej',
    );
    $after = app(ResultsCalculator::class)->projectTotals($edition);

    expect($before)->toHaveCount(0)
        ->and($updated->status)->toBe(VoteCardStatus::Accepted)
        ->and($updated->checkout_user_id)->toBe($operator->id)
        ->and($updated->checkout_date_time)->not->toBeNull()
        ->and($updated->notes)->toBe('Po weryfikacji ręcznej')
        ->and($after)->toHaveCount(1)
        ->and((int) $after->first()->points)->toBe(1);
});

it('registers paper vote cards with operator and paper numbering', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));
    $operator = User::factory()->create();
    $operator->givePermissionTo('vote_cards.manage');
    $identity = new VoterIdentityData(
        pesel: '44051401458',
        firstName: 'Jan',
        lastName: 'Kowalski',
        motherLastName: 'Nowak',
    );

    $voteCard = app(RegisterPaperVoteCardAction::class)->execute(
        $edition,
        $identity,
        [$project->id],
        [],
        $operator,
        [
            'citizen_confirm' => CitizenConfirmation::Living,
            'confirm_missing_category' => true,
        ],
    );

    expect($voteCard->digital)->toBeFalse()
        ->and($voteCard->card_no)->toBe(1)
        ->and($voteCard->created_by_id)->toBe($operator->id)
        ->and($edition->refresh()->current_paper_card_no)->toBe(1);
});

it('rejects paper vote card registration without permission', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));
    $operator = User::factory()->create();
    $identity = new VoterIdentityData(
        pesel: '44051401458',
        firstName: 'Jan',
        lastName: 'Kowalski',
        motherLastName: 'Nowak',
    );

    app(RegisterPaperVoteCardAction::class)->execute(
        $edition,
        $identity,
        [$project->id],
        [],
        $operator,
        [
            'citizen_confirm' => CitizenConfirmation::Living,
            'confirm_missing_category' => true,
        ],
    );
})->throws(DomainException::class, 'Brak uprawnień do rejestracji papierowej karty głosowania.');

it('aggregates accepted results by area and category', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $localArea = ProjectArea::query()->create(areaAttributes());
    $citywideArea = ProjectArea::query()->create(areaAttributes([
        'name' => 'Ogólnomiejskie',
        'symbol' => 'OGM',
        'is_local' => false,
    ]));
    $greenCategory = Category::query()->create(['name' => 'Zieleń']);
    $sportCategory = Category::query()->create(['name' => 'Sport']);
    $localProject = Project::query()->create(projectAttributes($edition->id, $localArea->id, [
        'category_id' => $greenCategory->id,
        'status' => ProjectStatus::Picked,
    ]));
    $citywideProject = Project::query()->create(projectAttributes($edition->id, $citywideArea->id, [
        'category_id' => $sportCategory->id,
        'title' => 'Boisko',
        'status' => ProjectStatus::Picked,
    ]));
    $voter = Voter::query()->create([
        'pesel' => '44051401458',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
        'age' => 82,
        'sex' => 'M',
    ]);
    $voteCard = VoteCard::query()->create([
        'budget_edition_id' => $edition->id,
        'voter_id' => $voter->id,
        'status' => VoteCardStatus::Accepted,
    ]);
    $voteCard->votes()->createMany([
        [
            'voter_id' => $voter->id,
            'project_id' => $localProject->id,
            'points' => 1,
        ],
        [
            'voter_id' => $voter->id,
            'project_id' => $citywideProject->id,
            'points' => 1,
        ],
    ]);

    $calculator = app(ResultsCalculator::class);
    $areaTotals = $calculator->areaTotals($edition);
    $categoryTotals = $calculator->categoryTotals($edition);

    expect($areaTotals)->toHaveCount(2)
        ->and($areaTotals->pluck('points', 'symbol')->map(fn ($points) => (int) $points)->all())
        ->toBe(['OGM' => 1, 'L1' => 1])
        ->and($categoryTotals->pluck('points', 'name')->map(fn ($points) => (int) $points)->all())
        ->toBe(['Sport' => 1, 'Zieleń' => 1]);
});

it('aggregates category totals through category pivot when project has many categories', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $greenCategory = Category::query()->create(['name' => 'Zieleń']);
    $sportCategory = Category::query()->create(['name' => 'Sport']);
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'category_id' => $greenCategory->id,
        'status' => ProjectStatus::Picked,
    ]));
    $project->categories()->sync([$greenCategory->id, $sportCategory->id]);
    $voter = Voter::query()->create([
        'pesel' => '44051401458',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
    ]);
    $voteCard = VoteCard::query()->create([
        'budget_edition_id' => $edition->id,
        'voter_id' => $voter->id,
        'status' => VoteCardStatus::Accepted,
    ]);
    $voteCard->votes()->create([
        'voter_id' => $voter->id,
        'project_id' => $project->id,
        'points' => 1,
    ]);

    $categoryTotals = app(ResultsCalculator::class)->categoryTotals($edition);

    expect($categoryTotals->pluck('points', 'name')->map(fn ($points) => (int) $points)->all())
        ->toBe(['Sport' => 1, 'Zieleń' => 1]);
});

it('builds vote card status and demographic reports without pii', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $acceptedVoter = Voter::query()->create([
        'pesel' => '44051401458',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
        'age' => 82,
        'sex' => 'M',
    ]);
    $minorVoter = Voter::query()->create([
        'pesel' => '10251500004',
        'first_name' => 'Anna',
        'last_name' => 'Nowak',
        'age' => 16,
        'sex' => 'K',
    ]);
    $rejectedVoter = Voter::query()->create([
        'pesel' => '02270803628',
        'first_name' => 'Piotr',
        'last_name' => 'Wiśniewski',
        'age' => 24,
        'sex' => 'M',
    ]);

    VoteCard::query()->create([
        'budget_edition_id' => $edition->id,
        'voter_id' => $acceptedVoter->id,
        'status' => VoteCardStatus::Accepted,
    ]);
    VoteCard::query()->create([
        'budget_edition_id' => $edition->id,
        'voter_id' => $minorVoter->id,
        'status' => VoteCardStatus::Accepted,
    ]);
    VoteCard::query()->create([
        'budget_edition_id' => $edition->id,
        'voter_id' => $rejectedVoter->id,
        'status' => VoteCardStatus::Rejected,
    ]);

    $report = app(VoteCardReportService::class);
    $statusCounts = $report->statusCounts($edition);
    $demographics = $report->acceptedVoterDemographics($edition);

    expect($statusCounts->all())->toBe([
        VoteCardStatus::Accepted->value => 2,
        VoteCardStatus::Rejected->value => 1,
    ])
        ->and($demographics['sex']->all())->toBe(['K' => 1, 'M' => 1])
        ->and($demographics['age_buckets'])->toMatchArray([
            'under_18' => 1,
            'over_60' => 1,
        ]);
});

it('builds legacy age group totals per project from accepted cards', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));

    foreach ([24, 40, 55, 72] as $index => $age) {
        $voter = Voter::query()->create([
            'pesel' => '4405140145'.$index,
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'age' => $age,
        ]);
        $voteCard = VoteCard::query()->create([
            'budget_edition_id' => $edition->id,
            'voter_id' => $voter->id,
            'status' => VoteCardStatus::Accepted,
        ]);
        $voteCard->votes()->create([
            'voter_id' => $voter->id,
            'project_id' => $project->id,
            'points' => 1,
        ]);
    }

    $rows = app(VoteCardReportService::class)->projectAgeGroupTotals($edition);
    $row = $rows->first();

    expect($rows)->toHaveCount(1)
        ->and((int) $row->age_16_30)->toBe(1)
        ->and((int) $row->age_31_45)->toBe(1)
        ->and((int) $row->age_46_60)->toBe(1)
        ->and((int) $row->age_61_plus)->toBe(1)
        ->and((int) $row->total)->toBe(4);
});

it('builds legacy sex totals per project from accepted cards', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));

    foreach (['K', 'F', 'M', null] as $index => $sex) {
        $voter = Voter::query()->create([
            'pesel' => '4405140145'.$index,
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'sex' => $sex,
        ]);
        $voteCard = VoteCard::query()->create([
            'budget_edition_id' => $edition->id,
            'voter_id' => $voter->id,
            'status' => VoteCardStatus::Accepted,
        ]);
        $voteCard->votes()->create([
            'voter_id' => $voter->id,
            'project_id' => $project->id,
            'points' => 1,
        ]);
    }

    $rows = app(VoteCardReportService::class)->projectSexTotals($edition);
    $row = $rows->first();

    expect($rows)->toHaveCount(1)
        ->and((int) $row->female)->toBe(2)
        ->and((int) $row->male)->toBe(1)
        ->and((int) $row->unknown)->toBe(1)
        ->and((int) $row->total)->toBe(4);
});

it('builds legacy card type totals per project from accepted cards', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'status' => ProjectStatus::Picked,
    ]));

    foreach ([true, false, false] as $index => $digital) {
        $voter = Voter::query()->create([
            'pesel' => '4405140145'.$index,
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
        ]);
        $voteCard = VoteCard::query()->create([
            'budget_edition_id' => $edition->id,
            'voter_id' => $voter->id,
            'status' => VoteCardStatus::Accepted,
            'digital' => $digital,
        ]);
        $voteCard->votes()->create([
            'voter_id' => $voter->id,
            'project_id' => $project->id,
            'points' => 1,
        ]);
    }

    $rows = app(VoteCardReportService::class)->projectCardTypeTotals($edition);
    $row = $rows->first();

    expect($rows)->toHaveCount(1)
        ->and((int) $row->digital)->toBe(1)
        ->and((int) $row->paper)->toBe(2)
        ->and((int) $row->total)->toBe(3);
});

it('builds legacy admin vote card rows with pii columns', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $voter = Voter::query()->create([
        'pesel' => '44051401458',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
    ]);

    VoteCard::query()->create([
        'budget_edition_id' => $edition->id,
        'voter_id' => $voter->id,
        'card_no' => 12,
        'digital' => false,
        'status' => VoteCardStatus::Verifying,
        'citizen_confirm' => CitizenConfirmation::Living,
        'notes' => 'Do sprawdzenia',
        'ip' => '127.0.0.1',
        'created_at' => '2025-04-10 12:00:00',
        'updated_at' => '2025-04-10 13:00:00',
    ]);

    $rows = app(VoteCardReportService::class)->adminVoteCardRows($edition);

    expect($rows)->toHaveCount(1)
        ->and($rows->first())->toMatchArray([
            'card_id' => '000012',
            'card_type' => 'papierowa',
            'pesel' => '44051401458',
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'city_statement' => 'Tak',
            'status' => 'rozpatrywana',
            'notes' => 'Do sprawdzenia',
            'ip' => '127.0.0.1',
        ]);
});

it('exports legacy admin vote cards csv with pii columns', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $voter = Voter::query()->create([
        'pesel' => '44051401458',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
    ]);

    VoteCard::query()->create([
        'budget_edition_id' => $edition->id,
        'voter_id' => $voter->id,
        'card_no' => 15,
        'digital' => true,
        'status' => VoteCardStatus::Accepted,
        'citizen_confirm' => CitizenConfirmation::Default,
        'notes' => 'OK',
        'ip' => '127.0.0.1',
        'created_at' => '2025-04-11 12:00:00',
        'updated_at' => '2025-04-11 13:00:00',
    ]);

    $csv = app(AdminVoteCardsCsvExporter::class)->export($edition);

    expect($csv)->toContain('"ID karty","Typ karty",PESEL,"Imie głosującego","Nazwisko głosującego","Oświadczenie zamieszkania",Status,Uwagi,"Data dodania","Data modyfikacji",IP')
        ->and($csv)->toContain('000015,interaktywna,44051401458,Jan,Kowalski,Nie,ważna,OK,"2025-04-11 12:00:00","2025-04-11 13:00:00",127.0.0.1');
});

it('builds and exports legacy submitted projects report', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes([
        'symbol' => 'P1',
    ]));
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'number' => 7,
        'title' => 'Park kieszonkowy',
        'submitted_at' => '2025-01-10 12:00:00',
    ]));
    Project::query()->create(projectAttributes($edition->id, $area->id, [
        'number' => 8,
        'title' => 'Za stary projekt',
        'submitted_at' => '2018-01-10 12:00:00',
    ]));

    $rows = app(ProjectReportService::class)->submittedProjectRows();
    $csv = app(SubmittedProjectsCsvExporter::class)->export();

    expect($rows)->toHaveCount(1)
        ->and($rows->first())->toMatchArray([
            'project_number' => 'P1/0007',
            'title' => $project->title,
            'submitted_at' => '2025-01-10 12:00:00',
        ])
        ->and($csv)->toContain('"Numer wniosku",Tytuł,"Data złożenia"')
        ->and($csv)->toContain('P1/0007,"Park kieszonkowy","2025-01-10 12:00:00"')
        ->and($csv)->not->toContain('Za stary projekt');
});

it('builds and exports legacy unsent advanced verifications report', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes([
        'symbol' => 'P1',
    ]));
    $department = Department::query()->create([
        'name' => 'Wydział testowy',
    ]);
    $user = User::factory()->create([
        'name' => 'operator',
    ]);
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'legacy_id' => 1332,
        'number' => 9,
        'title' => 'Projekt do opinii',
        'status' => ProjectStatus::Submitted,
    ]));
    $sentProject = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'number' => 10,
        'title' => 'Projekt wysłany',
        'status' => ProjectStatus::Submitted,
    ]));

    AdvancedVerification::query()->create([
        'project_id' => $project->id,
        'department_id' => $department->id,
        'created_by_id' => $user->id,
        'status' => 1,
        'raw_legacy_payload' => [],
    ]);
    AdvancedVerification::query()->create([
        'project_id' => $sentProject->id,
        'department_id' => $department->id,
        'created_by_id' => $user->id,
        'status' => 1,
        'sent_at' => '2025-03-20 12:00:00',
        'raw_legacy_payload' => [],
    ]);

    $rows = app(ProjectReportService::class)->unsentAdvancedVerificationRows();
    $csv = app(UnsentAdvancedVerificationsCsvExporter::class)->export();

    expect($rows)->toHaveCount(1)
        ->and($rows->first())->toMatchArray([
            'project_number' => 'P1/0009',
            'title' => 'Projekt do opinii',
            'department_name' => 'Wydział testowy',
            'author_name' => 'operator',
            'project_url' => 'https://sbownioski.szczecin.eu/task/1332',
        ])
        ->and($csv)->toContain('"Numer wniosku",Tytuł,"Nazwa wydziału","Nazwa autora","Link do projektu"')
        ->and($csv)->toContain('P1/0009,"Projekt do opinii","Wydział testowy",operator,https://sbownioski.szczecin.eu/task/1332')
        ->and($csv)->not->toContain('Projekt wysłany');
});

it('builds and exports legacy project corrections report', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes());
    $project = Project::query()->create(projectAttributes($edition->id, $area->id));

    ProjectCorrection::query()->create([
        'project_id' => $project->id,
        'allowed_fields' => [
            'title',
            'localization',
            'cost',
            'attachments',
        ],
        'notes' => 'Uzupełnij kosztorys',
        'correction_deadline' => '2025-02-20 12:00:00',
        'created_at' => '2025-02-10 12:00:00',
        'updated_at' => '2025-02-10 12:00:00',
    ]);

    $rows = app(ProjectReportService::class)->projectCorrectionRows();
    $csv = app(ProjectCorrectionsCsvExporter::class)->export();

    expect($rows)->toHaveCount(1)
        ->and($rows->first())->toMatchArray([
            'title' => 1,
            'taskTypeId' => 0,
            'localization' => 1,
            'cost' => 1,
            'attachments' => 1,
            'notes' => 'Uzupełnij kosztorys',
            'createdAt' => '2025-02-10 12:00:00',
            'correctionDeadline' => '2025-02-20 12:00:00',
        ])
        ->and($csv)->toContain('Tytuł,"Obszary Lokalne","Lokalizacja projektu","Mapka projektu"')
        ->and($csv)->toContain('1,0,1,0,0,0,0,0,0,0,1,0,0,0,0,1,0,"Uzupełnij kosztorys","2025-02-10 12:00:00","2025-02-20 12:00:00"');
});

it('builds and exports legacy project history report', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $area = ProjectArea::query()->create(areaAttributes([
        'legacy_id' => 20,
        'name' => 'Pogodno',
        'symbol' => 'P1',
    ]));
    $user = User::factory()->create([
        'name' => 'operator',
    ]);
    $project = Project::query()->create(projectAttributes($edition->id, $area->id, [
        'legacy_id' => 1332,
        'number' => 7,
    ]));

    ProjectVersion::query()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => ProjectStatus::Submitted,
        'data' => [
            'title' => 'Park kieszonkowy',
            'local' => 1,
            'taskTypeId' => 20,
            'argumentation' => 'Uzasadnienie',
            'localization' => 'Szczecin',
            'goal' => 'Cel',
            'description' => 'Opis',
            'recipients' => 'Mieszkańcy',
            'freeOfCharge' => 'Tak',
            'status' => ProjectStatus::Submitted->value,
        ],
        'files' => [],
        'costs' => [],
        'created_at' => '2025-01-11 12:00:00',
        'updated_at' => '2025-01-11 12:00:00',
    ]);

    $rows = app(ProjectReportService::class)->projectHistoryRows();
    $csv = app(ProjectHistoryCsvExporter::class)->export();

    expect($rows)->toHaveCount(1)
        ->and($rows->first())->toMatchArray([
            'project_id' => 1332,
            'project_number' => 'P1/0007',
            'title' => 'Park kieszonkowy',
            'project_category' => 'Projekt lokalny',
            'district' => 'Pogodno',
            'status' => ProjectStatus::Submitted->publicLabel(),
            'changed_at' => '2025-01-11 12:00:00',
            'changed_by' => 'operator',
        ])
        ->and($csv)->toContain('"Identyfikator wniosku","Numer wniosku",Tytuł,"Kategoria projektu",Dzielnica')
        ->and($csv)->toContain('1332,P1/0007,"Park kieszonkowy","Projekt lokalny",Pogodno,Uzasadnienie,Szczecin,Cel,Opis')
        ->and($csv)->toContain('operator');
});
