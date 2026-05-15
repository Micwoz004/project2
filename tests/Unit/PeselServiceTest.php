<?php

use App\Domain\Voting\Services\PeselService;
use Illuminate\Support\Carbon;

it('validates pesel checksum and derives voter metadata', function (): void {
    $service = new PeselService;

    expect($service->isValid('44051401458'))->toBeTrue()
        ->and($service->isValid('44051401459'))->toBeFalse()
        ->and($service->birthDate('44051401458')->toDateString())->toBe('1944-05-14')
        ->and($service->sex('44051401458'))->toBe('M');
});

it('detects parent consent requirement for minors', function (): void {
    $service = new PeselService;
    $now = Carbon::parse('2026-05-15 12:00:00', 'Europe/Warsaw');

    expect($service->requiresParentConsent('10251500000', $now))->toBeTrue();
});
