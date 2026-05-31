<?php

namespace Tests;

use App\Platform\Clients\Models\Client;
use App\Platform\Clients\Services\CurrentClient;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('clients')) {
            app(CurrentClient::class)->set(Client::default());
        }
    }
}
