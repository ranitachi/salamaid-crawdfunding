<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PublicAuthController extends Controller
{
    public function create(): View { return view('public.auth.login'); }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email:rfc,filter'], 'password' => ['required', 'string']]);
        if (! Auth::attempt(array_merge($credentials, ['is_active' => true]), $request->boolean('remember'))) {
            return back()->withInput($request->only('email', 'remember'))->withErrors(['email' => 'Email atau password tidak sesuai.']);
        }
        $request->session()->regenerate();
        return redirect()->intended(route('public.account'));
    }

    public function register(): View { return view('public.auth.register'); }

    public function forgotPassword(): View { return view('public.auth.forgot-password'); }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email:rfc,filter']]);
        Password::sendResetLink($request->only('email'));

        return back()->with('status_message', 'Jika email terdaftar, tautan reset password akan dikirim.');
    }

    public function resetForm(string $token): View
    {
        return view('public.auth.reset-password', compact('token'));
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email:rfc,filter'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($data, function (User $user, string $password): void {
            $user->forceFill(['password' => $password, 'remember_token' => null])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa.']);
        }

        return redirect()->route('public.login')->with('status_message', 'Password berhasil diperbarui. Silakan masuk.');
    }

    public function createAccount(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email:rfc,filter', 'max:190', 'unique:users,email'], 'password' => ['required', 'string', 'min:8', 'confirmed']]);
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'role' => 'user', 'is_active' => true]);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->intended(route('public.account'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('public.home');
    }
}
