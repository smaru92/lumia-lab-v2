<?php

namespace App\Filament\Resources\EquipmentSkill\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EquipmentSkillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('스킬 이름')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('스킬 설명')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
