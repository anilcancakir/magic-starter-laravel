<?php

namespace AnilcanCakir\MagicStarter\Filament\Resources\AiPrompts\Pages;

use AnilcanCakir\MagicStarter\Filament\Concerns\WritesThroughContracts;
use AnilcanCakir\MagicStarter\Filament\Resources\AiPrompts\AiPromptOverrideResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAiPromptOverride extends CreateRecord
{
    use WritesThroughContracts;

    protected static string $resource = AiPromptOverrideResource::class;
}
