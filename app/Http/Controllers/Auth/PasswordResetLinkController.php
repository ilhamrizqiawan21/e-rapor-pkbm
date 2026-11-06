<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetLinkRequest;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(PasswordResetLinkRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status !== PasswordBroker::RESET_LINK_SENT) {
            return back()->withInput()->withErrors(['email' => 'Tautan reset tidak dapat dikirim. Pastikan email terdaftar.']);
        }

        return back()->with('status', 'Tautan reset password telah dikirim ke email terdaftar.');
    }
}
