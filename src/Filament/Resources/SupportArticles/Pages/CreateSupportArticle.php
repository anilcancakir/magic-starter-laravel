<?php

namespace AnilcanCakir\MagicStarter\Filament\Resources\SupportArticles\Pages;

use AnilcanCakir\MagicStarter\Filament\Concerns\WritesThroughContracts;
use AnilcanCakir\MagicStarter\Filament\Resources\SupportArticles\SupportArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupportArticle extends CreateRecord
{
    use WritesThroughContracts;

    protected static string $resource = SupportArticleResource::class;
}
