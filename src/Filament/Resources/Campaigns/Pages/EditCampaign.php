<?php

namespace AnilcanCakir\MagicStarter\Filament\Resources\Campaigns\Pages;

use AnilcanCakir\MagicStarter\Filament\Concerns\WritesThroughContracts;
use AnilcanCakir\MagicStarter\Filament\Resources\Campaigns\CampaignResource;
use Filament\Resources\Pages\EditRecord;

class EditCampaign extends EditRecord
{
    use WritesThroughContracts;

    protected static string $resource = CampaignResource::class;
}
