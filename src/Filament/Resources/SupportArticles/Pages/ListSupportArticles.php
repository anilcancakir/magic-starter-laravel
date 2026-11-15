<?php

namespace AnilcanCakir\MagicStarter\Filament\Resources\SupportArticles\Pages;

use AnilcanCakir\MagicStarter\Filament\Resources\SupportArticles\SupportArticleResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListSupportArticles extends ListRecords
{
    protected static string $resource = SupportArticleResource::class;

    /**
     * A link to the create page rather than Filament's create action, which would build the
     * model itself and skip the resource's save.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('magic-starter::admin_support.articles.actions.create'))
                ->icon(Heroicon::OutlinedPlus)
                ->url(static fn (): string => SupportArticleResource::getUrl('create')),
        ];
    }
}
