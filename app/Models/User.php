<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleCode;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['institution_id', 'name', 'username', 'email', 'password', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements CanResetPasswordContract
{
    /** @use HasFactory<UserFactory> */
    use CanResetPassword, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class);
    }

    public function hasRole(RoleCode|string $role): bool
    {
        $code = $role instanceof RoleCode ? $role->value : $role;

        return $this->roles()->where('code', $code)->exists();
    }

    /**
     * @param  array<int, RoleCode|string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        $codes = array_map(
            static fn (RoleCode|string $role): string => $role instanceof RoleCode ? $role->value : $role,
            $roles,
        );

        return $this->roles()->whereIn('code', $codes)->exists();
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }
}
