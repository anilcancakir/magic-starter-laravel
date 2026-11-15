<?php

namespace AnilcanCakir\MagicStarter\Filament\Resources\AppReleases\Pages;

use AnilcanCakir\MagicStarter\Filament\Concerns\WritesThroughContracts;
use AnilcanCakir\MagicStarter\Filament\Resources\AppReleases\AppReleaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAppRelease extends CreateRecord
{
    use WritesThroughContracts;

    protected static string $resource = AppReleaseResource::class;
}
