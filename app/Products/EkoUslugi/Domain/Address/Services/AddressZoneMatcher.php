<?php

namespace App\Products\EkoUslugi\Domain\Address\Services;

use App\Products\EkoUslugi\Domain\Address\Models\EkoZone;
use App\Products\EkoUslugi\Domain\Address\Models\EkoZoneAddressRule;
use App\Products\EkoUslugi\Domain\Address\Models\ResidentAddress;
use Illuminate\Support\Facades\Log;

class AddressZoneMatcher
{
    public function match(ResidentAddress $address): ?EkoZone
    {
        Log::info('eko_uslugi.address.zone_match.start', [
            'resident_address_id' => $address->id,
            'user_id' => $address->user_id,
        ]);

        $buildingNumber = $this->buildingNumber($address->building_number);
        $numericBuildingNumber = $this->numericBuildingNumber($buildingNumber);

        $zone = EkoZone::query()
            ->active()
            ->whereHas('rules', function ($query) use ($address, $buildingNumber): void {
                $query
                    ->where(function ($query) use ($address): void {
                        $query->whereNull('locality')->orWhere('locality', $address->locality);
                    })
                    ->where(function ($query) use ($address): void {
                        $query->whereNull('street')->orWhere('street', $address->street);
                    })
                    ->where(function ($query) use ($buildingNumber): void {
                        $query
                            ->whereNull('exact_building_number')
                            ->orWhere('exact_building_number', $buildingNumber);
                    });
            })
            ->with('rules')
            ->orderBy('name')
            ->get()
            ->first(fn (EkoZone $zone): bool => $zone->rules->contains(
                fn ($rule): bool => $this->ruleMatchesBuildingNumber($rule, $buildingNumber, $numericBuildingNumber),
            ));

        Log::info('eko_uslugi.address.zone_match.success', [
            'resident_address_id' => $address->id,
            'eko_zone_id' => $zone?->id,
        ]);

        return $zone;
    }

    private function buildingNumber(string $value): string
    {
        return trim(mb_strtolower($value));
    }

    private function numericBuildingNumber(string $value): ?int
    {
        preg_match('/^\d+/', $value, $matches);

        return isset($matches[0]) ? (int) $matches[0] : null;
    }

    private function ruleMatchesBuildingNumber(EkoZoneAddressRule $rule, string $buildingNumber, ?int $numericBuildingNumber): bool
    {
        if (filled($rule->exact_building_number)) {
            return $this->buildingNumber((string) $rule->exact_building_number) === $buildingNumber;
        }

        if ($numericBuildingNumber === null) {
            return blank($rule->building_from) && blank($rule->building_to);
        }

        if (filled($rule->building_from) && $numericBuildingNumber < (int) $rule->building_from) {
            return false;
        }

        if (filled($rule->building_to) && $numericBuildingNumber > (int) $rule->building_to) {
            return false;
        }

        return match ($rule->parity) {
            'even' => $numericBuildingNumber % 2 === 0,
            'odd' => $numericBuildingNumber % 2 === 1,
            default => true,
        };
    }
}
