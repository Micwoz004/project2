<?php

namespace App\Platform\Clients\Concerns;

use App\Platform\Clients\Models\Client;
use App\Platform\Clients\Services\CurrentClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToClient
{
    public static function bootBelongsToClient(): void
    {
        static::addGlobalScope('client', function (Builder $builder): void {
            $clientId = app(CurrentClient::class)->id();

            if ($clientId !== null) {
                $builder->where($builder->getModel()->getTable().'.client_id', $clientId);
            }
        });

        static::creating(function ($model): void {
            if (blank($model->client_id)) {
                $model->client_id = app(CurrentClient::class)->id();
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
