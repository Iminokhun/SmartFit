<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use App\Models\Staff;


class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

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
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->hasRole('admin') || $this->role_id === 1,
            'manager' => $this->hasRole('manager', 'admin') || in_array($this->role_id, [1, 2], true),
            default => false,
        };
    }

    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    private function hasRole(string ...$names): bool
    {
        $roleName = strtolower((string) ($this->role?->name ?? ''));

        if ($roleName === '') {
            return false;
        }

        return in_array($roleName, array_map('strtolower', $names), true);
    }
}
