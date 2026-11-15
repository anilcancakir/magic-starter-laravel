<?php

namespace AnilcanCakir\MagicStarter\Filament\Resources\BillingGrants\Pages;

use AnilcanCakir\MagicStarter\Filament\Actions\GrantPlanAction;
use AnilcanCakir\MagicStarter\Filament\Resources\BillingGrants\BillingGrantResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListBillingGrants extends ListRecords
{
    protected static string $resource = BillingGrantResource::class;

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            GrantPlanAction::make(),
        ];
    }
}
