<?php

namespace AnilcanCakir\MagicStarter\Filament\Resources\SupportArticles\Pages;

use AnilcanCakir\MagicStarter\Filament\Concerns\WritesThroughContracts;
use AnilcanCakir\MagicStarter\Filament\Resources\SupportArticles\SupportArticleResource;
use AnilcanCakir\MagicStarter\Models\SupportArticle;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupportArticle extends EditRecord
{
    use WritesThroughContracts;

    protected static string $resource = SupportArticleResource::class;

    /**
     * One approve action per locale, then the delete. The delete goes through the model so its
     * observer drops the cached corpus, which a query-level delete would skip.
     */
    protected function getHeaderActions(): array
    {
        return [
            ...array_map(SupportArticleResource::approveAction(...), SupportArticleResource::locales()),
            DeleteAction::make()
                ->using(static fn (SupportArticle $record): ?bool => $record->delete()),
        ];
    }
}
