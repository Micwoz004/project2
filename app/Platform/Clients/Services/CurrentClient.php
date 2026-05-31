<?php

namespace App\Platform\Clients\Services;

use App\Platform\Clients\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

class CurrentClient
{
    private ?Client $client = null;

    public function set(Client $client): void
    {
        $this->client = $client;
        $this->setPermissionsTeam($client->id);
    }

    public function get(): ?Client
    {
        return $this->client;
    }

    public function id(): ?int
    {
        return $this->resolveDefault()?->id;
    }

    public function require(): Client
    {
        $client = $this->resolveDefault();

        if (! $client instanceof Client) {
            throw new RuntimeException('Nie można rozpoznać aktywnego klienta platformy.');
        }

        return $client;
    }

    public function resolveFromRequest(Request $request): ?Client
    {
        if (! $this->canUseClientTable()) {
            return null;
        }

        $slug = $request->headers->get('X-Client-Slug')
            ?: (string) config('platform.default_client_slug', Client::DEFAULT_SLUG);

        $client = Client::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $client instanceof Client && $slug === Client::DEFAULT_SLUG) {
            $client = Client::default();
        }

        if ($client instanceof Client) {
            $this->set($client);
        }

        return $client;
    }

    public function resolveDefault(): ?Client
    {
        if ($this->client instanceof Client) {
            return $this->client;
        }

        if (! $this->canUseClientTable()) {
            return null;
        }

        $client = Client::default();
        $this->set($client);

        return $client;
    }

    private function setPermissionsTeam(?int $clientId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($clientId);
    }

    private function canUseClientTable(): bool
    {
        return Schema::hasTable('clients');
    }
}
