<?php

use App\Platform\Clients\Models\Client;
use App\Platform\Clients\Services\CurrentClient;
use App\Platform\Products\Enums\ProductKey;
use App\Products\CivicBudget\Domain\BudgetEditions\Models\BudgetEdition;

it('exposes enabled products for mobile clients', function (): void {
    $this->getJson(route('mobile.products.index'))
        ->assertOk()
        ->assertJsonPath('items.0.key', ProductKey::CivicBudget->value)
        ->assertJsonPath('items.0.label', ProductKey::CivicBudget->label());
});

it('blocks civic budget mobile routes when product is disabled for current client', function (): void {
    BudgetEdition::query()->create(editionAttributes());

    Client::default()
        ->products()
        ->where('product_key', ProductKey::CivicBudget->value)
        ->update(['enabled' => false]);

    $this->getJson(route('mobile.civic-budget.overview'))
        ->assertNotFound();
});

it('scopes civic budget records to the current client', function (): void {
    $currentClient = app(CurrentClient::class);
    $defaultClient = Client::default();
    $defaultEdition = BudgetEdition::query()->create(editionAttributes());

    $otherClient = Client::query()->create([
        'name' => 'Drugi klient',
        'slug' => 'drugi-klient',
        'is_active' => true,
        'settings' => [],
    ]);

    $currentClient->set($otherClient);
    $otherEdition = BudgetEdition::query()->create(editionAttributes([
        'propose_start' => now()->subYear(),
    ]));

    expect(BudgetEdition::query()->pluck('id')->all())->toBe([$otherEdition->id]);

    $currentClient->set($defaultClient);

    expect(BudgetEdition::query()->pluck('id')->all())->toBe([$defaultEdition->id]);
});
