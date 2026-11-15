<?php

namespace AnilcanCakir\MagicStarter\Tests\Fixtures;

use AnilcanCakir\MagicStarter\Filament\Concerns\AuthorizesAdminPanel;
use AnilcanCakir\MagicStarter\Models\PushDevice;
use AnilcanCakir\MagicStarter\Traits\MustVerifyEmail;
use AnilcanCakir\MagicStarter\Traits\TwoFactorAuthenticatable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

/**
 * A panel user for Filament tests, gated by the package's own access trait.
 */
class ConcreteAdminUser extends ConcreteUser implements FilamentUser, MustVerifyEmailContract
{
    use AuthorizesAdminPanel;
    use HasApiTokens;
    use MustVerifyEmail;
    use TwoFactorAuthenticatable;

    /**
     * @return HasMany<PushDevice, $this>
     */
    public function pushDevices(): HasMany
    {
        return $this->hasMany(PushDevice::class, 'user_id');
    }
}
