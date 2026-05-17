<?php

use App\Domain\BudgetEditions\Actions\EnsureContentPagesForBudgetEditionAction;
use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\BudgetEditions\Services\BudgetEditionScheduleValidator;
use App\Domain\Settings\Models\ApplicationSetting;
use App\Domain\Settings\Models\ContentPage;
use App\Domain\Settings\Services\ApplicationSettings;
use App\Domain\Settings\Services\ContentPageResolver;

it('accepts a valid budget edition schedule', function (): void {
    app(BudgetEditionScheduleValidator::class)->assertValid(editionAttributes());

    expect(true)->toBeTrue();
});

it('rejects budget editions overlapping by legacy propose start rule', function (): void {
    BudgetEdition::query()->create(editionAttributes());

    app(BudgetEditionScheduleValidator::class)->assertValid([
        'propose_start' => now()->subWeek(),
        'propose_end' => now()->addDay(),
        'pre_voting_verification_end' => now()->addDays(2),
        'voting_start' => now()->addDays(3),
        'voting_end' => now()->addDays(4),
        'post_voting_verification_end' => now()->addDays(5),
        'result_announcement_end' => now()->addDays(6),
    ]);
})->throws(DomainException::class, 'Głosowania muszą być w odrębnych terminach.');

it('allows updating the same budget edition without self-overlap rejection', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());

    app(BudgetEditionScheduleValidator::class)->assertValid([
        ...editionAttributes(),
        'propose_start' => now()->subMonths(4),
    ], $edition->id);

    expect(true)->toBeTrue();
});

it('rejects invalid legacy date order', function (): void {
    app(BudgetEditionScheduleValidator::class)->assertValid([
        ...editionAttributes(),
        'voting_start' => now()->addDays(2),
        'voting_end' => now()->addDay(),
    ]);
})->throws(DomainException::class, 'Data zakończenia głosowania na propozycje projektów zadań musi być późniejsza od daty rozpoczęcia głosowania na propozycje projektów zadań.');

it('creates legacy content page symbols for a budget edition once', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    $action = app(EnsureContentPagesForBudgetEditionAction::class);

    $action->execute($edition);
    $action->execute($edition);

    expect(ContentPage::query()->where('budget_edition_id', $edition->id)->count())
        ->toBe(count(ContentPage::LEGACY_SYMBOLS))
        ->and(ContentPage::query()->pluck('symbol')->sort()->values()->all())
        ->toBe(collect(ContentPage::LEGACY_SYMBOLS)->sort()->values()->all());
});

it('reads imported legacy settings as raw and typed values', function (): void {
    ApplicationSetting::query()->create([
        'category' => 'owner',
        'key' => 'name',
        'value' => 'Urząd Miasta Szczecin',
    ]);
    ApplicationSetting::query()->create([
        'category' => 'owner',
        'key' => 'sessionTime',
        'value' => '45',
    ]);
    ApplicationSetting::query()->create([
        'category' => 'owner',
        'key' => 'ZkPresidentAbsence',
        'value' => '1',
    ]);

    $settings = app(ApplicationSettings::class);

    expect($settings->string('owner', 'name'))->toBe('Urząd Miasta Szczecin')
        ->and($settings->integer('owner', 'sessionTime'))->toBe(45)
        ->and($settings->boolean('owner', 'ZkPresidentAbsence'))->toBeTrue()
        ->and($settings->string('owner', 'missing', 'fallback'))->toBe('fallback');
});

it('resolves legacy content pages with SYMBOL_VOID fallback from settings', function (): void {
    $edition = BudgetEdition::query()->create(editionAttributes());
    ApplicationSetting::query()->create([
        'category' => 'owner',
        'key' => 'pageProcessAbsence',
        'value' => '<p>Proces głosowania jest niedostępny.</p>',
    ]);
    ContentPage::query()->create([
        'budget_edition_id' => $edition->id,
        'symbol' => ContentPage::SYMBOL_WELCOME,
        'body' => '<p>Witamy w głosowaniu.</p>',
    ]);

    $resolver = app(ContentPageResolver::class);

    expect($resolver->bodyFor($edition, ContentPage::SYMBOL_VOID))->toBe('<p>Proces głosowania jest niedostępny.</p>')
        ->and($resolver->bodyFor($edition, ContentPage::SYMBOL_WELCOME))->toBe('<p>Witamy w głosowaniu.</p>')
        ->and($resolver->bodyFor(null, ContentPage::SYMBOL_WELCOME))->toBeNull();
});
