<?php

namespace App\Filament\Resources\EquipmentSkill;

use App\Filament\Resources\EquipmentSkill\Pages\CreateEquipmentSkill;
use App\Filament\Resources\EquipmentSkill\Pages\EditEquipmentSkill;
use App\Filament\Resources\EquipmentSkill\Pages\ListEquipmentSkill;
use App\Filament\Resources\EquipmentSkill\Schemas\EquipmentSkillForm;
use App\Filament\Resources\EquipmentSkill\Tables\EquipmentSkillTable;
use App\Models\EquipmentSkill;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EquipmentSkillResource extends Resource
{
    protected static ?string $model = EquipmentSkill::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = '장비 스킬';

    protected static ?string $modelLabel = '장비 스킬';

    public static function form(Schema $schema): Schema
    {
        return EquipmentSkillForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EquipmentSkillTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEquipmentSkill::route('/'),
            'create' => CreateEquipmentSkill::route('/create'),
            'edit' => EditEquipmentSkill::route('/{record}/edit'),
        ];
    }
}
