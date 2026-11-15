<?php

namespace AnilcanCakir\MagicStarter\Actions;

use AnilcanCakir\MagicStarter\Contracts\DisablesTwoFactorAuthentication;

class DisableTwoFactorAuthentication implements DisablesTwoFactorAuthentication
{
    /**
     * Disable two factor authentication for the user.
     */
    public function disable(mixed $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }
}
