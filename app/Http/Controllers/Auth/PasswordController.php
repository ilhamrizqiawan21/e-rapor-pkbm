<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('account.password');
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $password = $request->string('password')->toString();
        $user->forceFill([
            'password' => $password,
            'remember_token' => Str::random(60),
        ])->save();

        Auth::logoutOtherDevices($password);

        return back()->with('status', 'Password berhasil diperbarui.');
    }
}
