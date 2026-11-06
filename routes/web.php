<?php

use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Admin\InstitutionController;
use App\Http\Controllers\Admin\InstitutionFormatSettingsController;
use App\Http\Controllers\Admin\InstitutionPreviewController;
use App\Http\Controllers\Admin\SignatorySettingsController;
use App\Http\Controllers\Admin\UserPasswordController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/admin', DashboardController::class)->middleware('role:ADMIN')->name('dashboard.admin');
    Route::get('/dashboard/guru', DashboardController::class)->middleware('role:GURU')->name('dashboard.guru');
    Route::get('/dashboard/kepsek', DashboardController::class)->middleware('role:KEPSEK')->name('dashboard.kepsek');

    Route::get('/account/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/account/password', [PasswordController::class, 'update'])->name('password.update');
    Route::get('/account/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    Route::middleware('role:ADMIN')->prefix('admin/users/{user}/password')->name('admin.users.password.')->group(function (): void {
        Route::get('/', [UserPasswordController::class, 'edit'])->name('edit');
        Route::put('/', [UserPasswordController::class, 'update'])->name('update');
    });

    Route::middleware('role:ADMIN')->prefix('admin')->name('admin.')->group(function (): void {
        Route::resource('institutions', InstitutionController::class);
        Route::get('institutions/{institution}/preview', [InstitutionPreviewController::class, 'show'])
            ->name('institutions.preview');
        Route::delete('institutions/{institution}/logo', [InstitutionController::class, 'destroyLogo'])
            ->name('institutions.logo.destroy');

        Route::get('signatory-settings', [SignatorySettingsController::class, 'edit'])
            ->name('signatory-settings.edit');
        Route::put('signatory-settings', [SignatorySettingsController::class, 'update'])
            ->name('signatory-settings.update');

        Route::get('settings/format', [InstitutionFormatSettingsController::class, 'edit'])
            ->name('settings.format.edit');
        Route::put('settings/format', [InstitutionFormatSettingsController::class, 'update'])
            ->name('settings.format.update');
    });
});

Route::view('/components-showcase', 'components-showcase')->name('components.showcase');
