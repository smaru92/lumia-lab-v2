<?php

namespace App\Filament\Resources\EquipmentSkill\Pages;

use App\Filament\Resources\EquipmentSkill\EquipmentSkillResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentSkill extends EditRecord
{
    protected static string $resource = EquipmentSkillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
