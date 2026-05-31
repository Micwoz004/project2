<?php

use App\Models\User;
use App\Platform\Clients\Models\Client;
use App\Platform\Clients\Services\CurrentClient;
use App\Platform\Products\Enums\ProductKey;
use App\Products\EcoServices\Domain\Address\Models\EcoZone;
use App\Products\EcoServices\Domain\Address\Models\ResidentAddress;
use App\Products\EcoServices\Domain\AirQuality\Actions\SyncAirQualityStationsAction;
use App\Products\EcoServices\Domain\AirQuality\Models\AirQualityReading;
use App\Products\EcoServices\Domain\AirQuality\Models\AirQualityStation;
use App\Products\EcoServices\Domain\Notifications\Actions\QueueCollectionReminderNotificationsAction;
use App\Products\EcoServices\Domain\Notifications\Models\NotificationEvent;
use App\Products\EcoServices\Domain\Notifications\Models\NotificationTemplate;
use App\Products\EcoServices\Domain\Schedule\Actions\ImportCollectionScheduleCsvAction;
use App\Products\EcoServices\Domain\Schedule\Models\CollectionSchedule;
use App\Products\EcoServices\Domain\Schedule\Models\CollectionScheduleDate;
use App\Products\EcoServices\Domain\Waste\Models\WasteFraction;
use App\Products\EcoServices\Domain\Waste\Models\WasteItem;
use Illuminate\Support\Facades\Http;

it('exposes eco services as an enabled platform product', function (): void {
    $response = $this->getJson(route('mobile.products.index'))
        ->assertOk();

    expect(collect($response->json('items'))->pluck('key')->all())
        ->toContain(ProductKey::EcoServices->value);
});

it('blocks eco services routes when product is disabled for current client', function (): void {
    Client::default()
        ->products()
        ->where('product_key', ProductKey::EcoServices->value)
        ->update(['enabled' => false]);

    $this->getJson(route('mobile.eco-services.overview'))
        ->assertNotFound();

    $this->get(route('eco-services.home'))
        ->assertNotFound();
});

it('scopes waste search results by current client', function (): void {
    $defaultFraction = WasteFraction::query()->create([
        'name' => 'Papier',
        'status' => 'active',
    ]);
    WasteItem::query()->create([
        'eco_waste_fraction_id' => $defaultFraction->id,
        'name' => 'Karton',
        'instruction' => 'Wyrzuć do papieru.',
        'status' => 'active',
    ]);

    $otherClient = Client::query()->create([
        'name' => 'Drugi klient',
        'slug' => 'drugi-klient',
        'is_active' => true,
        'settings' => [],
    ]);
    $otherClient->products()->create([
        'product_key' => ProductKey::EcoServices->value,
        'enabled' => true,
        'settings' => [],
    ]);

    app(CurrentClient::class)->set($otherClient);
    $otherFraction = WasteFraction::query()->create([
        'name' => 'Szkło',
        'status' => 'active',
    ]);
    WasteItem::query()->create([
        'eco_waste_fraction_id' => $otherFraction->id,
        'name' => 'Karton lokalny',
        'instruction' => 'Inna instrukcja.',
        'status' => 'active',
    ]);

    app(CurrentClient::class)->set(Client::default());

    $this->getJson(route('mobile.eco-services.waste.search', ['query' => 'karton']))
        ->assertOk()
        ->assertJsonPath('items.0.instruction', 'Wyrzuć do papieru.')
        ->assertJsonCount(1, 'items');

    $this->withHeader('X-Client-Slug', 'drugi-klient')
        ->getJson(route('mobile.eco-services.waste.search', ['query' => 'karton']))
        ->assertOk()
        ->assertJsonPath('items.0.instruction', 'Inna instrukcja.')
        ->assertJsonCount(1, 'items');
});

it('matches resident address to a zone and returns upcoming collections', function (): void {
    $zone = EcoZone::query()->create([
        'code' => 'A',
        'name' => 'Sektor A',
        'status' => 'active',
    ]);
    $zone->rules()->create([
        'locality' => 'Szczecin',
        'street' => 'Jasna',
        'parity' => 'all',
    ]);
    $fraction = WasteFraction::query()->create([
        'name' => 'Bio',
        'status' => 'active',
    ]);
    $schedule = CollectionSchedule::query()->create([
        'name' => 'Harmonogram 2026',
        'status' => 'active',
    ]);
    CollectionScheduleDate::query()->create([
        'eco_collection_schedule_id' => $schedule->id,
        'eco_zone_id' => $zone->id,
        'eco_waste_fraction_id' => $fraction->id,
        'collection_date' => now()->addDays(2)->toDateString(),
    ]);

    $resident = User::factory()->create([
        'status' => true,
        'password' => 'secret-password',
    ]);
    $token = $this->postJson(route('mobile.resident.login'), [
        'email' => $resident->email,
        'password' => 'secret-password',
    ])->assertOk()->json('accessToken');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('mobile.eco-services.resident.addresses.store'), [
            'label' => 'Dom',
            'locality' => 'Szczecin',
            'street' => 'Jasna',
            'building_number' => '12',
        ])
        ->assertCreated()
        ->assertJsonPath('item.zone.name', 'Sektor A');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('mobile.eco-services.resident.schedules.upcoming'))
        ->assertOk()
        ->assertJsonPath('items.0.fraction.name', 'Bio')
        ->assertJsonPath('items.0.zone.name', 'Sektor A');
});

it('matches address rules by building number range and parity', function (): void {
    $zone = EcoZone::query()->create([
        'code' => 'EVEN',
        'name' => 'Parzyste 10-20',
        'status' => 'active',
    ]);
    $zone->rules()->create([
        'locality' => 'Szczecin',
        'street' => 'Jasna',
        'building_from' => '10',
        'building_to' => '20',
        'parity' => 'even',
    ]);

    $resident = User::factory()->create([
        'status' => true,
        'password' => 'secret-password',
    ]);
    $token = $this->postJson(route('mobile.resident.login'), [
        'email' => $resident->email,
        'password' => 'secret-password',
    ])->assertOk()->json('accessToken');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('mobile.eco-services.resident.addresses.store'), [
            'locality' => 'Szczecin',
            'street' => 'Jasna',
            'building_number' => '12A',
        ])
        ->assertCreated()
        ->assertJsonPath('item.zone.name', 'Parzyste 10-20');
});

it('imports collection schedule dates from csv', function (): void {
    $zone = EcoZone::query()->create([
        'code' => 'A',
        'name' => 'Sektor A',
        'status' => 'active',
    ]);
    $fraction = WasteFraction::query()->create([
        'name' => 'Papier',
        'status' => 'active',
    ]);
    $path = tempnam(sys_get_temp_dir(), 'eco-schedule-');
    file_put_contents($path, "zone_code;fraction;collection_date\nA;Papier;2026-06-10\n");

    $stats = app(ImportCollectionScheduleCsvAction::class)->execute($path, 'Harmonogram testowy');

    expect($stats['imported'])->toBe(1)
        ->and(CollectionSchedule::query()->where('name', 'Harmonogram testowy')->exists())->toBeTrue()
        ->and(CollectionScheduleDate::query()
            ->where('eco_zone_id', $zone->id)
            ->where('eco_waste_fraction_id', $fraction->id)
            ->whereDate('collection_date', '2026-06-10')
            ->exists())->toBeTrue();
});

it('syncs GIOS air quality stations and readings', function (): void {
    Http::fake([
        'https://api.gios.gov.pl/pjp-api/rest/station/findAll' => Http::response([
            [
                'id' => 101,
                'stationName' => 'Szczecin Andrzejewskiego',
                'gegrLat' => '53.401',
                'gegrLon' => '14.492',
                'city' => ['name' => 'Szczecin'],
                'addressStreet' => 'Andrzejewskiego',
            ],
        ]),
        'https://api.gios.gov.pl/pjp-api/rest/aqindex/getIndex/101' => Http::response([
            'stCalcDate' => '2026-05-31 10:00:00',
            'stIndexLevel' => ['id' => 1, 'indexLevelName' => 'Bardzo dobry'],
        ]),
    ]);

    $stats = app(SyncAirQualityStationsAction::class)->execute();

    expect($stats)->toBe(['stations' => 1, 'readings' => 1])
        ->and(AirQualityStation::query()->where('external_id', '101')->exists())->toBeTrue()
        ->and(AirQualityReading::query()->where('parameter_code', 'AQI')->where('index_category_name', 'Bardzo dobry')->exists())->toBeTrue();
});

it('queues collection reminder notification events once', function (): void {
    $zone = EcoZone::query()->create([
        'code' => 'A',
        'name' => 'Sektor A',
        'status' => 'active',
    ]);
    $fraction = WasteFraction::query()->create([
        'name' => 'Bio',
        'status' => 'active',
    ]);
    $schedule = CollectionSchedule::query()->create([
        'name' => 'Harmonogram',
        'status' => 'active',
    ]);
    $date = CollectionScheduleDate::query()->create([
        'eco_collection_schedule_id' => $schedule->id,
        'eco_zone_id' => $zone->id,
        'eco_waste_fraction_id' => $fraction->id,
        'collection_date' => now()->addDay()->toDateString(),
    ]);
    $resident = User::factory()->create(['status' => true]);
    ResidentAddress::query()->create([
        'user_id' => $resident->id,
        'eco_zone_id' => $zone->id,
        'locality' => 'Szczecin',
        'building_number' => '12',
        'is_active' => true,
    ]);
    NotificationTemplate::query()->create([
        'name' => 'Przypomnienie',
        'trigger_type' => 'collection_reminder',
        'status' => 'active',
        'push_enabled' => true,
        'push_body_template' => 'Jutro odbiór odpadów.',
        'days_before' => 1,
    ]);

    $firstRun = app(QueueCollectionReminderNotificationsAction::class)->execute();
    $secondRun = app(QueueCollectionReminderNotificationsAction::class)->execute();

    expect($firstRun['events'])->toBe(1)
        ->and($secondRun['events'])->toBe(0)
        ->and(NotificationEvent::query()->where('event_type', 'collection_reminder')->count())->toBe(1)
        ->and(NotificationEvent::query()->first()?->payload['schedule_date_id'])->toBe($date->id);
});

it('renders the eco services resident spa shell', function (): void {
    $this->get(route('eco-services.home'))
        ->assertOk()
        ->assertSee('Ekousługi')
        ->assertSee('window.BO_SPA', false);
});
