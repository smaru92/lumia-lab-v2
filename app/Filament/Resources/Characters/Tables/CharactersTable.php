<?php

namespace App\Filament\Resources\Characters\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CharactersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('max_hp')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_hp_by_lv')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_mp')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_mp_by_lv')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('init_extra_point')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_extra_point')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('attack_power')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('attack_power_by_lv')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('deffence')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('deffence_by_lv')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('hp_regen')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('hp_regen_by_lv')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sp_regen')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sp_regen_by_lv')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('attack_speed')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('attack_speed_limit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('attack_speed_min')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('move_speed')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sight_range')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
