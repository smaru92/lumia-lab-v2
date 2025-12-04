<?php

namespace App\Filament\Resources\EquipmentSkill\Schemas;

use Filament\Forms\Components\Select;
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

                Select::make('grade')
                    ->label('스킬 등급')
                    ->options([
                        'Epic' => '영웅',
                        'Legend' => '전설',
                        'Mythic' => '초월',
                    ])
                    ->nullable()
                    ->placeholder('등급 선택'),

                TextInput::make('sub_category')
                    ->label('2차분류')
                    ->maxLength(255)
                    ->nullable()
                    ->placeholder('2차분류 입력'),

                Textarea::make('description')
                    ->label('스킬 설명')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
