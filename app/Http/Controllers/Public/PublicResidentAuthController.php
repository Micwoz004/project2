<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PublicResidentAuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        Log::info('resident_auth.login.start', [
            'ip' => $request->ip(),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials + ['status' => true], $request->boolean('remember'))) {
            Log::warning('resident_auth.login.rejected', [
                'email_hash' => hash('sha256', mb_strtolower($credentials['email'])),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Nieprawidłowy adres e-mail lub hasło.']);
        }

        $request->session()->regenerate();

        Log::info('resident_auth.login.success', [
            'user_id' => $request->user()->id,
        ]);

        return redirect()->intended(route('public.resident.dashboard'));
    }

    public function register(Request $request): RedirectResponse
    {
        Log::info('resident_auth.register.start', [
            'ip' => $request->ip(),
        ]);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:127'],
            'last_name' => ['required', 'string', 'max:127'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user = User::query()->create([
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();

        Log::info('resident_auth.register.success', [
            'user_id' => $user->id,
            'email_verification_sent' => true,
        ]);

        return redirect()->route('verification.notice')
            ->with('status', 'Konto mieszkańca zostało utworzone. Wysłaliśmy link weryfikacyjny na podany adres e-mail.');
    }

    public function verifyEmail(EmailVerificationRequest $request): RedirectResponse
    {
        Log::info('resident_auth.email_verify.start', [
            'user_id' => $request->user()->id,
        ]);

        $request->fulfill();

        Log::info('resident_auth.email_verify.success', [
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('public.resident.dashboard')
            ->with('status', 'Adres e-mail został potwierdzony.');
    }

    public function resendEmailVerification(Request $request): RedirectResponse
    {
        Log::info('resident_auth.email_verify_resend.start', [
            'user_id' => $request->user()->id,
        ]);

        if ($request->user()->hasVerifiedEmail()) {
            Log::info('resident_auth.email_verify_resend.skipped_already_verified', [
                'user_id' => $request->user()->id,
            ]);

            return redirect()->route('public.resident.dashboard')
                ->with('status', 'Adres e-mail jest już potwierdzony.');
        }

        $request->user()->sendEmailVerificationNotification();

        Log::info('resident_auth.email_verify_resend.success', [
            'user_id' => $request->user()->id,
        ]);

        return back()->with('status', 'Wysłaliśmy nowy link do potwierdzenia adresu e-mail.');
    }

    public function sendPasswordResetLink(Request $request): RedirectResponse
    {
        Log::info('resident_auth.password_reset_link.start', [
            'ip' => $request->ip(),
        ]);

        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($data);
        $emailHash = hash('sha256', mb_strtolower($data['email']));

        if ($status === Password::RESET_THROTTLED) {
            Log::warning('resident_auth.password_reset_link.rejected_throttled', [
                'email_hash' => $emailHash,
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('resident_auth.password_reset_link.success', [
                'email_hash' => $emailHash,
            ]);
        } else {
            Log::info('resident_auth.password_reset_link.accepted_without_user', [
                'email_hash' => $emailHash,
            ]);
        }

        return back()
            ->withInput($request->only('email'))
            ->with('status', 'Jeśli konto istnieje, wyślemy wiadomość z linkiem do ustawienia nowego hasła.');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        Log::info('resident_auth.password_reset.start', [
            'ip' => $request->ip(),
        ]);

        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset($data, function (User $user, string $password): void {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));

            Log::info('resident_auth.password_reset.success', [
                'user_id' => $user->id,
            ]);
        });

        if ($status !== Password::PASSWORD_RESET) {
            Log::warning('resident_auth.password_reset.rejected', [
                'email_hash' => hash('sha256', mb_strtolower($data['email'])),
                'status' => $status,
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        return redirect()->route('login')
            ->with('status', 'Hasło zostało zmienione. Możesz się zalogować.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Log::info('resident_auth.logout.start', [
            'user_id' => $request->user()?->id,
        ]);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('resident_auth.logout.success');

        return redirect()->route('public.home');
    }
}
