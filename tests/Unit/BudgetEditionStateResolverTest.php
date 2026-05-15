<?php

use App\Domain\BudgetEditions\Enums\BudgetEditionState;
use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\BudgetEditions\Services\BudgetEditionStateResolver;
use Illuminate\Support\Carbon;

it('resolves legacy voting window states', function (): void {
    $edition = new BudgetEdition([
        'propose_start' => '2026-01-01 00:00:00',
        'propose_end' => '2026-01-31 23:59:59',
        'pre_voting_verification_end' => '2026-02-28 23:59:59',
        'voting_start' => '2026-03-10 00:00:00',
        'voting_end' => '2026-03-20 23:59:59',
        'post_voting_verification_end' => '2026-03-31 23:59:59',
        'result_announcement_end' => '2026-04-30 23:59:59',
    ]);

    $resolver = new BudgetEditionStateResolver;

    expect($resolver->resolve($edition, Carbon::parse('2026-01-15 12:00:00', 'Europe/Warsaw')))
        ->toBe(BudgetEditionState::Propose)
        ->and($resolver->resolve($edition, Carbon::parse('2026-03-01 12:00:00', 'Europe/Warsaw')))
        ->toBe(BudgetEditionState::PreVotingCorrection)
        ->and($resolver->resolve($edition, Carbon::parse('2026-03-15 12:00:00', 'Europe/Warsaw')))
        ->toBe(BudgetEditionState::Voting)
        ->and($resolver->resolve($edition, Carbon::parse('2026-05-01 00:00:00', 'Europe/Warsaw')))
        ->toBe(BudgetEditionState::Inactive);
});

it('keeps legacy inactive boundaries for edition start and final end', function (): void {
    $edition = new BudgetEdition([
        'propose_start' => '2026-01-01 00:00:00',
        'propose_end' => '2026-01-31 23:59:59',
        'pre_voting_verification_end' => '2026-02-28 23:59:59',
        'voting_start' => '2026-03-10 00:00:00',
        'voting_end' => '2026-03-20 23:59:59',
        'post_voting_verification_end' => '2026-03-31 23:59:59',
        'result_announcement_end' => '2026-04-30 23:59:59',
    ]);

    $resolver = new BudgetEditionStateResolver;

    expect($resolver->resolve($edition, Carbon::parse('2026-01-01 00:00:00', 'Europe/Warsaw')))
        ->toBe(BudgetEditionState::Inactive)
        ->and($resolver->resolve($edition, Carbon::parse('2026-04-30 23:59:59', 'Europe/Warsaw')))
        ->toBe(BudgetEditionState::Inactive);
});
