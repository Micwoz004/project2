<?php

namespace App\Products\EcoUslugi\Domain\Address\Services;

use App\Products\EcoUslugi\Domain\Address\Models\EcoZone;
use App\Products\EcoUslugi\Domain\Address\Models\EcoZoneAddressRule;
use App\Products\EcoUslugi\Domain\Address\Models\ResidentAddress;
use Illuminate\Support\Facades\Log;

class AddressZoneMatcher
{
    public function match(ResidentAddress $address): ?EcoZone
    {
        Log::info('eco_uslugi.address.zone_match.start', [
            'resident_address_id' => $address->id,
            'user_id' => $address->user_id,
        ]);

        $buildingNumber = $this->buildingNumber($address->building_number);
        $numericBuildingNumber = $this->numericBuildingNumber($buildingNumber);

        $zone = EcoZone::query()
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
            ->first(fn (EcoZone $zone): bool => $zone->rules->contains(
                fn ($rule): bool => $this->ruleMatchesBuildingNumber($rule, $buildingNumber, $numericBuildingNumber),
            ));

        Log::info('eco_uslugi.address.zone_match.success', [
            'resident_address_id' => $address->id,
            'eco_zone_id' => $zone?->id,
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

    private function ruleMatchesBuildingNumber(EcoZoneAddressRule $rule, string $buildingNumber, ?int $numericBuildingNumber): bool
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
