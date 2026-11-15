<?php

namespace AnilcanCakir\MagicStarter\Filament\Resources\Audits\Pages;

use AnilcanCakir\MagicStarter\Filament\Resources\Audits\AuditResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAudit extends ViewRecord
{
    protected static string $resource = AuditResource::class;

    /**
     * The record title alone, such as `Team updated`: a read-only page has no
     * other verb, so Filament's "View" prefix says nothing.
     */
    public function getTitle(): string
    {
        return (string) static::getResource()::getRecordTitle($this->getRecord());
    }
}
