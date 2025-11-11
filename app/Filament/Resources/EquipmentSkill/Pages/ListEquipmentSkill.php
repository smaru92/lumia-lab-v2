<?php

namespace App\Filament\Resources\EquipmentSkill\Pages;

use App\Filament\Resources\EquipmentSkill\EquipmentSkillResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentSkill extends ListRecords
{
    protected static string $resource = EquipmentSkillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
