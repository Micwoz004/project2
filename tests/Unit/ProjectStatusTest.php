<?php

use App\Domain\Projects\Enums\ProjectStatus;

it('keeps legacy project status values and labels', function (): void {
    expect(ProjectStatus::Picked->value)->toBe(5)
        ->and(ProjectStatus::TeamRejectedFinally->value)->toBe(-14)
        ->and(ProjectStatus::RejectedFormally->isRejected())->toBeTrue()
        ->and(ProjectStatus::Picked->publicLabel())->toBe('na listę do głosowania');
});
