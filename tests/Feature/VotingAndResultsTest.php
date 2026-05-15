<?php

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Models\Category;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectArea;
use App\Domain\Reports\Services\VoteCardReportService;
use App\Domain\Results\Services\ResultsCalculator;
use App\Domain\Users\Actions\SyncSystemRolesAndPermissionsAction;
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
