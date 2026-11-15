<?php

namespace AnilcanCakir\MagicStarter\Filament\Resources\AppReleases\Pages;

use AnilcanCakir\MagicStarter\Contracts\DeletesAppReleases;
use AnilcanCakir\MagicStarter\Filament\Concerns\WritesThroughContracts;
use AnilcanCakir\MagicStarter\Filament\Resources\AppReleases\AppReleaseResource;
use AnilcanCakir\MagicStarter\Filament\Support\ContractAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditAppRelease extends EditRecord
{
    use WritesThroughContracts;

    protected static string $resource = AppReleaseResource::class;

    /**
     * A plain action rather than Filament's delete action: that one calls
     * `$record->delete()` and would skip the contract.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('delete')
                ->label(__('magic-starter::admin_misc.releases.actions.delete.label'))
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('magic-starter::admin_misc.releases.actions.delete.heading'))
                ->modalDescription(__('magic-starter::admin_misc.releases.actions.delete.description'))
                ->successNotificationTitle(__('magic-starter::admin_misc.releases.actions.delete.success'))
                ->successRedirectUrl(fn (): string => AppReleaseResource::getUrl('index'))
                ->action(function (Action $action): void {
                    $release = $this->getRecord();
                    $actor = $this->panelActor();

                    ContractAction::run(
                        $action,
                        static fn () => app(DeletesAppReleases::class)->delete($actor, $release),
                        'release.deleted',
                        $release,
                    );
                }),
        ];
    }
}
