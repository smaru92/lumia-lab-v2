<?php

namespace App\Filament\Resources\VersionHistories\Pages;

use App\Filament\Resources\VersionHistories\VersionHistoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVersionHistory extends CreateRecord
{
    protected static string $resource = VersionHistoryResource::class;
}
