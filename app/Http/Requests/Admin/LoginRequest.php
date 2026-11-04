<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\OrganizationAdminAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Only active users with an admin role can log in. Failures share one
     * generic message so the form does not reveal which accounts exist.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $authenticated = Auth::guard('web')->attemptWhen(
            $this->only('email', 'password'),
            fn (User $user) => $user->isAdmin(),
            $this->boolean('remember'),
        );

        if (! $authenticated) {
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah, atau akun tidak memiliki akses admin.',
            ]);
        }

        $user = $this->user();
        if ($user instanceof User && ! OrganizationAdminAccess::canAccess($user)) {
            Auth::guard('web')->logout();
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'email' => 'Akun lembaga belum diverifikasi atau tidak memiliki keanggotaan lembaga yang aktif.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    private function throttleKey(): string
    {
        return 'admin-login|'.Str::transliterate(Str::lower($this->string('email'))).'|'.$this->ip();
    }
}
