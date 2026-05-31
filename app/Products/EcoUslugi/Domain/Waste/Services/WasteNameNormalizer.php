<?php

namespace App\Products\EcoUslugi\Domain\Waste\Services;

use Illuminate\Support\Str;

class WasteNameNormalizer
{
    public function normalize(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }
}
