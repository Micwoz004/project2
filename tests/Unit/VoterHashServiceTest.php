<?php

use App\Domain\Voting\Services\VoterHashService;

it('normalizes voter identity like the legacy verification hash', function (): void {
    $service = new VoterHashService;

    expect($service->normalizeName(' Łukasz-Żółć_Kowalski '))->toBe('LUKASZZOLCKOWALSKI')
        ->and($service->legacyHash('12345678901', 'Łukasz', 'Żółć-Kowalski', 'Nowak'))
        ->toBe(md5('12345678901LUKASZZOLCKOWALSKINOWAKD0FB5FC74E'));
});
