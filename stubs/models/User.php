<?php

namespace App\Models;

use AnilcanCakir\MagicStarter\Support\ConditionallyUsesUuids;
use AnilcanCakir\MagicStarter\Traits\HasGuestSupport;
use AnilcanCakir\MagicStarter\Traits\HasNotifications;
use AnilcanCakir\MagicStarter\Traits\HasProfilePhoto;
use AnilcanCakir\MagicStarter\Traits\HasTeams;
use AnilcanCakir\MagicStarter\Traits\MustVerifyEmail;
use AnilcanCakir\MagicStarter\Traits\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmailContract
{
    // Billing with the user as the billable subject: opt in to `entitled()`, `onTier()` and `tierAtLeast()`
    // reads that answer for every rail by importing `AnilcanCakir\MagicStarter\Traits\HasEntitlement`
    // and adding `use HasEntitlement;` here. Under team billing it belongs on the Team model instead.
    use ConditionallyUsesUuids;
    use HasApiTokens;
    use HasFactory;
    use HasGuestSupport;
    use HasNotifications;
    use HasProfilePhoto;
    use HasTeams;
    use MustVerifyEmail;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'phone_country',
        'locale',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'is_guest' => 'boolean',
        ];
    }
}
