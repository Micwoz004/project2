<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\UpdateResidentAccountRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class PublicResidentAccountController extends Controller
{
    public function __invoke(UpdateResidentAccountRequest $request): RedirectResponse
    {
        $user = $request->user();

        Log::info('resident_account.update.start', [
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

        Log::info('resident_account.update.success', [
            'user_id' => $user->id,
            'password_changed' => $passwordChanged,
        ]);

        return redirect()
            ->route('public.resident.account')
            ->with('status', 'Dane konta zostały zapisane.');
    }
}
