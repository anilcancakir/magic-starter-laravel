<?php

namespace AnilcanCakir\MagicStarter\Filament\Resources\Users\Pages;

use AnilcanCakir\MagicStarter\Filament\Concerns\WritesThroughContracts;
use AnilcanCakir\MagicStarter\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use WritesThroughContracts;

    protected static string $resource = UserResource::class;
}
