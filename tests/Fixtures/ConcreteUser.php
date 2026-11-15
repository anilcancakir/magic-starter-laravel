<?php

namespace AnilcanCakir\MagicStarter\Tests\Fixtures;

use AnilcanCakir\MagicStarter\Support\ConditionallyUsesUuids;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;

class ConcreteUser extends Model implements AuthenticatableContract
{
    use \AnilcanCakir\MagicStarter\Traits\HasGuestSupport;
    use \AnilcanCakir\MagicStarter\Traits\HasNotifications;
    use \AnilcanCakir\MagicStarter\Traits\HasProfilePhoto;
    use \AnilcanCakir\MagicStarter\Traits\HasSocialAccounts;
    use \AnilcanCakir\MagicStarter\Traits\HasTeams;
    use AuthenticatableTrait;
    use Authorizable;
    use ConditionallyUsesUuids;

    protected $table = 'users';

    protected $guarded = [];
}
