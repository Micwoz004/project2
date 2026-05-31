<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\UpdateResidentAccountRequest;
use App\Products\CivicBudget\Http\Resources\Mobile\MobileCivicBudgetPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MobileResidentAccountController extends Controller
{
    public function __construct(
        private readonly MobileCivicBudgetPayload $payload,
    ) {}

    public function update(UpdateResidentAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        Log::info('mobile_resident_account.update.start', [
            'user_id' => $user->id,
        ]);

        $data = $request->validated();
        $passwordChanged = filled($data['password'] ?? null);

        $user->forceFill([
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'street' => $data['street'] ?? null,
            'house_no' => $data['house_no'] ?? null,
            'flat_no' => $data['flat_no'] ?? null,
            'post_code' => $data['post_code'] ?? null,
            'city' => $data['city'] ?? null,
        ]);

        if ($passwordChanged) {
            $user->password = $data['password'];
        }

        $user->save();

        Log::info('mobile_resident_account.update.success', [
            'user_id' => $user->id,
            'password_changed' => $passwordChanged,
        ]);

        return response()->json([
            'user' => $this->payload->user($user->refresh()),
            'message' => 'Dane konta zostały zapisane.',
        ]);
    }
}
