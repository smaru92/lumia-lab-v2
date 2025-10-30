<?php

namespace App\Filament\Resources\VersionHistories\Pages;

use App\Filament\Resources\VersionHistories\VersionHistoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVersionHistories extends ListRecords
{
    protected static string $resource = VersionHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
