<?php

namespace App\Domain\Voting\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class PeselService
{
    public function isValid(string $pesel): bool
    {
        if (! preg_match('/^[0-9]{11}$/', $pesel) || (int) $pesel === 0) {
            return false;
        }

        $weights = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3];
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += $weight * (int) $pesel[$index];
        }

        $control = 10 - ($sum % 10);
        $control = $control === 10 ? 0 : $control;

        return $control === (int) $pesel[10];
    }

    public function birthDate(string $pesel): CarbonImmutable
    {
        $year = (int) substr($pesel, 0, 2) + 1900;
        $month = (int) substr($pesel, 2, 2);
        $day = (int) substr($pesel, 4, 2);

        if ($month > 20 && $month < 40) {
            $month -= 20;
            $year += 100;
        }

        if ($month > 80 && $month < 100) {
            $month -= 80;
            $year -= 100;
        }

        return CarbonImmutable::create($year, $month, $day, 0, 0, 0, 'Europe/Warsaw');
    }

    public function age(string $pesel, ?CarbonInterface $now = null): int
    {
        $now = $now ?? CarbonImmutable::now('Europe/Warsaw');

        return (int) $this->birthDate($pesel)->diffInYears($now);
    }

    public function requiresParentConsent(string $pesel, ?CarbonInterface $now = null): bool
    {
        return $this->age($pesel, $now) < 18;
    }

    public function sex(string $pesel): string
    {
        return ((int) $pesel[9] % 2) === 0 ? 'K' : 'M';
    }
}
