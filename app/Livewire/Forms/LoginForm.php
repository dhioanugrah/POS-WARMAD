<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string')]
    public string $login = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials. Accepts either an
     * email address or a username (AUTH-01).
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $field = filter_var($this->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::query()->where($field, $this->login)->first();

        if (! $user || ! Auth::attempt([$field => $this->login, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.login' => trans('auth.failed'),
            ]);
        }

        if (! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'form.login' => 'Akun Anda nonaktif, hubungi pemilik toko.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $user->forceFill(['last_login_at' => now()])->save();
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->login).'|'.request()->ip());
    }
}
