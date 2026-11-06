<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserPasswordController extends Controller
{
    public function edit(Request $request, User $user): View
    {
        $this->ensureSameInstitution($request, $user);

        return view('admin.users.password', ['user' => $user]);
    }

    public function update(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        $this->ensureSameInstitution($request, $user);

        $user->forceFill([
            'password' => $request->string('password')->toString(),
            'remember_token' => Str::random(60),
        ])->save();

        return back()->with('status', 'Password pengguna berhasil direset oleh Admin.');
    }

    private function ensureSameInstitution(Request $request, User $user): void
    {
        abort_unless($request->user()?->institution_id === $user->institution_id, 404);
    }
}
