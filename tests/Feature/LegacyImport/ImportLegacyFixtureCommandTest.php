<?php

use App\Domain\BudgetEditions\Models\BudgetEdition;
use App\Domain\LegacyImport\Models\LegacyImportBatch;
use Illuminate\Support\Facades\File;

it('imports normalized legacy json through artisan command', function (): void {
    $path = storage_path('framework/testing/legacy-import-command.json');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, json_encode([
        'taskgroups' => [[
            'id' => 10,
            'proposeStart' => '2025-01-01 00:00:00',
            'proposeEnd' => '2025-02-01 00:00:00',
            'preVotingVerificationEnd' => '2025-03-01 00:00:00',
            'votingStart' => '2025-04-01 00:00:00',
            'votingEnd' => '2025-04-15 23:59:59',
            'postVotingVerificationEnd' => '2025-05-01 00:00:00',
            'resultAnnouncementEnd' => '2025-06-01 00:00:00',
        ]],
    ], JSON_THROW_ON_ERROR));

    $this->artisan('sbo:legacy-import', [
        'path' => $path,
        '--source' => 'command-test',
    ])->assertSuccessful();

    $batch = LegacyImportBatch::query()->firstOrFail();

    expect($batch->source_path)->toBe('command-test')
        ->and($batch->stats['taskgroups'])->toBe(1)
        ->and(BudgetEdition::query()->where('legacy_id', 10)->exists())->toBeTrue();
});

it('rejects unreadable legacy import file path', function (): void {
    $this->artisan('sbo:legacy-import', [
        'path' => storage_path('framework/testing/missing-import.json'),
    ])->assertFailed();

    expect(LegacyImportBatch::query()->exists())->toBeFalse();
});
