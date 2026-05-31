<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileApiToken;
use App\Models\User;
use App\Platform\Clients\Services\CurrentClient;
use App\Products\CivicBudget\Http\Resources\Mobile\MobileCivicBudgetPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class MobileResidentAuthController extends Controller
{
    public function __construct(
        private readonly MobileCivicBudgetPayload $payload,
    ) {}

    public function login(Request $request): JsonResponse
    {
        Log::info('mobile_resident_auth.login.start', [
            'ip' => $request->ip(),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $credentials['email'])
            ->where('status', true)
            ->first();

        if (! $user instanceof User || ! Hash::check($credentials['password'], $user->password)) {
            Log::warning('mobile_resident_auth.login.rejected', [
                'email_hash' => hash('sha256', mb_strtolower($credentials['email'])),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Nieprawidłowy adres e-mail lub hasło.',
            ]);
        }

        [$token, $plainToken] = MobileApiToken::issueFor($user);

        Log::info('mobile_resident_auth.login.success', [
            'user_id' => $user->id,
            'token_id' => $token->id,
        ]);

        return response()->json($this->sessionPayload($user, $plainToken));
    }

    public function register(Request $request): JsonResponse
    {
        Log::info('mobile_resident_auth.register.start', [
            'ip' => $request->ip(),
        ]);

        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:127'],
            'lastName' => ['required', 'string', 'max:127'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user = User::query()->create([
            'name' => trim($data['firstName'].' '.$data['lastName']),
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'status' => true,
        ]);

        $user->ensureClientMembership(app(CurrentClient::class)->require());
        $user->sendEmailVerificationNotification();
        [$token, $plainToken] = MobileApiToken::issueFor($user);

        Log::info('mobile_resident_auth.register.success', [
            'user_id' => $user->id,
            'token_id' => $token->id,
            'email_verification_sent' => true,
        ]);

        return response()->json($this->sessionPayload($user, $plainToken), 201);
    }

    public function me(Request $request): JsonResponse
    {
        Log::info('mobile_resident_auth.me.start', [
            'user_id' => $request->user()->id,
        ]);

        Log::info('mobile_resident_auth.me.success', [
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'user' => $this->payload->user($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Log::info('mobile_resident_auth.logout.start', [
            'user_id' => $request->user()->id,
        ]);

        $token = MobileApiToken::findValid($request->bearerToken());
        $token?->delete();

        Log::info('mobile_resident_auth.logout.success', [
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Wylogowano.',
        ]);
    }

    private function sessionPayload(User $user, string $plainToken): array
    {
        return [
            'accessToken' => $plainToken,
            'tokenType' => 'Bearer',
            'user' => $this->payload->user($user),
        ];
    }
}
