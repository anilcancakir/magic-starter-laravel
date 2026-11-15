<?php

namespace AnilcanCakir\MagicStarter\Filament\Resources\Users\Pages;

use AnilcanCakir\MagicStarter\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('magic-starter::admin_users.actions.create'))
                ->icon(Heroicon::OutlinedPlus)
                ->url(static fn (): string => UserResource::getUrl('create')),
        ];
    }
}
