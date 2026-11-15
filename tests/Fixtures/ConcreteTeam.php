<?php

namespace AnilcanCakir\MagicStarter\Tests\Fixtures;

use AnilcanCakir\MagicStarter\Models\Team;

class ConcreteTeam extends Team
{
    protected $table = 'teams';

    protected $fillable = [
        'name',
        'personal_team',
        'user_id',
    ];
}
