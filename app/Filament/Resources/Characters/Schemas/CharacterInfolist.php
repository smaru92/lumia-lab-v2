<?php

namespace App\Filament\Resources\Characters\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CharacterInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID')
                    ->numeric(),
                TextEntry::make('name')
                    ->placeholder('-'),
                TextEntry::make('max_hp')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('max_hp_by_lv')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('max_mp')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('max_mp_by_lv')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('init_extra_point')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('max_extra_point')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('attack_power')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('attack_power_by_lv')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('deffence')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('deffence_by_lv')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('hp_regen')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('hp_regen_by_lv')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('sp_regen')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('sp_regen_by_lv')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('attack_speed')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('attack_speed_limit')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('attack_speed_min')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('move_speed')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('sight_range')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
