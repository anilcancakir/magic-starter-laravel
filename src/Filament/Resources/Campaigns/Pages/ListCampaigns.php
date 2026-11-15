<?php

namespace AnilcanCakir\MagicStarter\Filament\Resources\Campaigns\Pages;

use AnilcanCakir\MagicStarter\Filament\Resources\Campaigns\CampaignResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;

    /**
     * A link to the create page rather than Filament's create action, which would build the
     * model itself and skip the contract.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('magic-starter::admin_misc.campaigns.actions.create'))
                ->icon(Heroicon::OutlinedPlus)
                ->url(static fn (): string => CampaignResource::getUrl('create')),
        ];
    }
}
