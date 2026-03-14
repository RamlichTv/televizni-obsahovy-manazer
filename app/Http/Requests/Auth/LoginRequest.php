<?php

namespace App\Http\Requests\Auth;

use App\Support\Events\EventLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validate login attempts and record failed authentication outcomes as events.
 */
class LoginRequest extends FormRequest
{
    /**
     * Determine whether the request is authorized.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Authenticate.
     *
     * @return void
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('username', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // Keep rejected credentials visible in the audit trail without storing the password.
            EventLogger::record(
                typeName: 'user_login_failed',
                message: 'Login failed for username "' . (string) $this->input('username') . '".',
                context: [
                    'action' => 'auth.login',
                    'username' => (string) $this->input('username'),
                    'ip' => (string) $this->ip(),
                    'reason' => 'invalid_credentials',
                ],
            );

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure is not rate limited.
     *
     * @return void
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        // Emit a distinct event when rate limiting, so brute-force lockouts are easy to spot.
        EventLogger::record(
            typeName: 'user_login_failed',
            message: 'Login blocked by rate limiter for username "' . (string) $this->input('username') . '".',
            context: [
                'action' => 'auth.login',
                'username' => (string) $this->input('username'),
                'ip' => (string) $this->ip(),
                'reason' => 'throttled',
                'seconds_remaining' => $seconds,
            ],
        );

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Throttle key.
     *
     * @return string
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }
}
