<?php

namespace App\Http\Middleware;

use App\Models\MobileApiToken;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = MobileApiToken::findValid($request->bearerToken());
        $user = $token?->user;

        if (! $token instanceof MobileApiToken || ! $user instanceof User || $user->status !== true) {
            Log::warning('mobile_api.auth.rejected', [
                'ip' => $request->ip(),
            ]);

            return new JsonResponse([
                'message' => 'Nie jesteś zalogowany.',
            ], 401);
        }

        $token->forceFill([
            'last_used_at' => now(),
        ])->save();

        $request->setUserResolver(fn (): User => $user);
        Auth::setUser($user);

        return $next($request);
    }
}
