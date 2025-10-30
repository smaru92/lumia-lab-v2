<?php

namespace App\Filament\Resources\VersionHistories\Pages;

use App\Filament\Resources\VersionHistories\VersionHistoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditVersionHistory extends EditRecord
{
    protected static string $resource = VersionHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
