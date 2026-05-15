<?php

use App\Domain\Dictionaries\Enums\DictionaryKind;
use App\Domain\Dictionaries\Models\DictionaryEntry;
use App\Domain\Dictionaries\Services\LegacyDictionaryImportService;

it('imports legacy name dictionaries with source table scoped legacy ids', function (): void {
    $stats = app(LegacyDictionaryImportService::class)->import([
        'firstnamedictionary' => [
            ['id' => 1, 'value' => 'Jan'],
        ],
        'lastnamedictionary' => [
            ['id' => 1, 'value' => 'Kowalski'],
        ],
        'motherlastnamedictionary' => [
            ['id' => 1, 'value' => 'Nowak'],
        ],
    ]);

    expect($stats)->toBe([
        'firstnamedictionary' => 1,
        'lastnamedictionary' => 1,
        'motherlastnamedictionary' => 1,
    ])
        ->and(DictionaryEntry::query()->count())->toBe(3)
        ->and(DictionaryEntry::query()->where('source_table', 'firstnamedictionary')->firstOrFail()->kind)
        ->toBe(DictionaryKind::FirstName)
        ->and(DictionaryEntry::query()->where('source_table', 'lastnamedictionary')->firstOrFail()->value)
        ->toBe('KOWALSKI');
});

it('keeps legacy dictionary import idempotent', function (): void {
    $payload = [
        'firstnamedictionary' => [
            ['id' => 10, 'value' => 'Anna'],
        ],
    ];

    app(LegacyDictionaryImportService::class)->import($payload);
    app(LegacyDictionaryImportService::class)->import([
        'firstnamedictionary' => [
            ['id' => 10, 'value' => 'Anna Maria'],
        ],
    ]);

    $entry = DictionaryEntry::query()->where('source_table', 'firstnamedictionary')->where('legacy_id', 10)->firstOrFail();

    expect(DictionaryEntry::query()->count())->toBe(1)
        ->and($entry->value)->toBe('ANNA MARIA');
});
