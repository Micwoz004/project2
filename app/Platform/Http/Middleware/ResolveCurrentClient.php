<?php

namespace App\Platform\Http\Middleware;

use App\Platform\Clients\Services\CurrentClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentClient
{
    public function __construct(
        private readonly CurrentClient $currentClient,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->currentClient->resolveFromRequest($request);

        return $next($request);
    }
}
