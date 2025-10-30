<?php

namespace App\Filament\Resources\VersionHistories\Pages;

use App\Filament\Resources\VersionHistories\VersionHistoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVersionHistory extends ViewRecord
{
    protected static string $resource = VersionHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
