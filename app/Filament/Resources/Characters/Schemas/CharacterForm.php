<?php

namespace App\Filament\Resources\Characters\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CharacterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('이름')
                    ->default(null),
                TextInput::make('max_hp')
                    ->label('최대 HP')
                    ->numeric()
                    ->default(null),
                TextInput::make('max_hp_by_lv')
                    ->label('레벨당 최대 HP')
                    ->numeric()
                    ->default(null),
                TextInput::make('max_mp')
                    ->label('최대 MP')
                    ->numeric()
                    ->default(null),
                TextInput::make('max_mp_by_lv')
                    ->label('레벨당 최대 MP')
                    ->numeric()
                    ->default(null),
                TextInput::make('init_extra_point')
                    ->label('초기 추가 포인트')
                    ->numeric()
                    ->default(null),
                TextInput::make('max_extra_point')
                    ->label('최대 추가 포인트')
                    ->numeric()
                    ->default(null),
                TextInput::make('attack_power')
                    ->label('공격력')
                    ->numeric()
                    ->default(null),
                TextInput::make('attack_power_by_lv')
                    ->label('레벨당 공격력')
                    ->numeric()
                    ->default(null),
                TextInput::make('deffence')
                    ->label('방어력')
                    ->numeric()
                    ->default(null),
                TextInput::make('deffence_by_lv')
                    ->label('레벨당 방어력')
                    ->numeric()
                    ->default(null),
                TextInput::make('hp_regen')
                    ->label('HP 재생')
                    ->numeric()
                    ->default(null),
                TextInput::make('hp_regen_by_lv')
                    ->label('레벨당 HP 재생')
                    ->numeric()
                    ->default(null),
                TextInput::make('sp_regen')
                    ->label('SP 재생')
                    ->numeric()
                    ->default(null),
                TextInput::make('sp_regen_by_lv')
                    ->label('레벨당 SP 재생')
                    ->numeric()
                    ->default(null),
                TextInput::make('attack_speed')
                    ->label('공격 속도')
                    ->numeric()
                    ->default(null),
                TextInput::make('attack_speed_limit')
                    ->label('최대 공격 속도')
                    ->numeric()
                    ->default(null),
                TextInput::make('attack_speed_min')
                    ->label('최소 공격 속도')
                    ->numeric()
                    ->default(null),
                TextInput::make('move_speed')
                    ->label('이동 속도')
                    ->numeric()
                    ->default(null),
                TextInput::make('sight_range')
                    ->label('시야 범위')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
