<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuditLogger $audit): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        $user = $request->user();
        $audit->record($user, 'auth.login', User::class, $user->getKey(), null, [
            'ip' => $request->ip(),
        ]);

        return redirect()->intended($this->dashboardRoute($user));
    }

    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();
        $audit->record($user, 'auth.logout', User::class, $user?->getKey() ?? 0);
        auth()->guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah keluar dari aplikasi.');
    }

    private function dashboardRoute(?User $user): string
    {
        abort_unless($user, 403);

        return match (true) {
            $user->hasRole('ADMIN') => route('dashboard.admin'),
            $user->hasRole('KEPSEK') => route('dashboard.kepsek'),
            $user->hasRole('GURU') => route('dashboard.guru'),
            default => route('dashboard'),
        };
    }
}
