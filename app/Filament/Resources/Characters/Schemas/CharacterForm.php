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
                    ->default(null),
                TextInput::make('max_hp')
                    ->numeric()
                    ->default(null),
                TextInput::make('max_hp_by_lv')
                    ->numeric()
                    ->default(null),
                TextInput::make('max_mp')
                    ->numeric()
                    ->default(null),
                TextInput::make('max_mp_by_lv')
                    ->numeric()
                    ->default(null),
                TextInput::make('init_extra_point')
                    ->numeric()
                    ->default(null),
                TextInput::make('max_extra_point')
                    ->numeric()
                    ->default(null),
                TextInput::make('attack_power')
                    ->numeric()
                    ->default(null),
                TextInput::make('attack_power_by_lv')
                    ->numeric()
                    ->default(null),
                TextInput::make('deffence')
                    ->numeric()
                    ->default(null),
                TextInput::make('deffence_by_lv')
                    ->numeric()
                    ->default(null),
                TextInput::make('hp_regen')
                    ->numeric()
                    ->default(null),
                TextInput::make('hp_regen_by_lv')
                    ->numeric()
                    ->default(null),
                TextInput::make('sp_regen')
                    ->numeric()
                    ->default(null),
                TextInput::make('sp_regen_by_lv')
                    ->numeric()
                    ->default(null),
                TextInput::make('attack_speed')
                    ->numeric()
                    ->default(null),
                TextInput::make('attack_speed_limit')
                    ->numeric()
                    ->default(null),
                TextInput::make('attack_speed_min')
                    ->numeric()
                    ->default(null),
                TextInput::make('move_speed')
                    ->numeric()
                    ->default(null),
                TextInput::make('sight_range')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
