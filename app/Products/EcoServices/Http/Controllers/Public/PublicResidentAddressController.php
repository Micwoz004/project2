<?php

namespace App\Products\EcoServices\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Products\EcoServices\Domain\Address\Models\ResidentAddress;
use App\Products\EcoServices\Domain\Address\Services\AddressZoneMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicResidentAddressController extends Controller
{
    public function store(Request $request, AddressZoneMatcher $matcher): RedirectResponse
    {
        $validated = $this->validatedAddress($request);

        Log::info('eco_services.resident_address.store.start', [
            'user_id' => $request->user()->id,
        ]);

        $address = ResidentAddress::query()->create([
            ...$validated,
            'user_id' => $request->user()->id,
            'is_active' => ! ResidentAddress::query()->where('user_id', $request->user()->id)->exists(),
        ]);
        $zone = $matcher->match($address);
        $address->forceFill(['eco_zone_id' => $zone?->id])->save();

        Log::info('eco_services.resident_address.store.success', [
            'user_id' => $request->user()->id,
            'resident_address_id' => $address->id,
            'eco_zone_id' => $zone?->id,
        ]);

        return redirect()->route('eco-services.addresses')->with('status', 'Adres został zapisany.');
    }

    public function activate(Request $request, ResidentAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        Log::info('eco_services.resident_address.activate.start', [
            'user_id' => $request->user()->id,
            'resident_address_id' => $address->id,
        ]);

        ResidentAddress::query()
            ->where('user_id', $request->user()->id)
            ->update(['is_active' => false]);
        $address->forceFill(['is_active' => true])->save();

        Log::info('eco_services.resident_address.activate.success', [
            'user_id' => $request->user()->id,
            'resident_address_id' => $address->id,
        ]);

        return redirect()->route('eco-services.addresses')->with('status', 'Adres aktywny został zmieniony.');
    }

    public function destroy(Request $request, ResidentAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        Log::info('eco_services.resident_address.delete.start', [
            'user_id' => $request->user()->id,
            'resident_address_id' => $address->id,
        ]);

        $address->delete();

        Log::info('eco_services.resident_address.delete.success', [
            'user_id' => $request->user()->id,
            'resident_address_id' => $address->id,
        ]);

        return redirect()->route('eco-services.addresses')->with('status', 'Adres został usunięty.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAddress(Request $request): array
    {
        return $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'building_type' => ['nullable', 'string', 'max:30'],
            'province' => ['nullable', 'string', 'max:100'],
            'county' => ['nullable', 'string', 'max:100'],
            'commune' => ['nullable', 'string', 'max:100'],
            'locality' => ['required', 'string', 'max:150'],
            'street' => ['nullable', 'string', 'max:180'],
            'building_number' => ['required', 'string', 'max:20'],
            'apartment_number' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['nullable', 'string', 'max:12'],
        ]);
    }
}
