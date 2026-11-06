<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = $this->string('login')->trim()->toString();
        $user = User::query()
            ->where(function ($query) use ($login): void {
                $query->where('username', $login)->orWhere('email', $login);
            })
            ->first();

        if (! $user || $user->status !== UserStatus::Active || ! Hash::check($this->string('password')->toString(), $user->password)) {
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'login' => 'Nama pengguna/email atau kata sandi salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Auth::guard('web')->login($user, $this->boolean('remember'));
    }

    public function throttleKey(): string
    {
        return 'login:'.hash('sha256', Str::lower($this->string('login')->trim()->toString()).'|'.$this->ip());
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        throw ValidationException::withMessages([
            'login' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($this->throttleKey()).' detik.',
        ]);
    }
}
