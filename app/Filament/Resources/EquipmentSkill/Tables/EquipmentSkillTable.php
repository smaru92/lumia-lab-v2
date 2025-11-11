<?php

namespace App\Filament\Resources\EquipmentSkill\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EquipmentSkillTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('스킬 이름')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('grade')
                    ->label('등급')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn (string $state = null): string => match($state) {
                        'Epic' => '영웅',
                        'Legend' => '전설',
                        'Mythic' => '초월',
                        default => $state ?? '-',
                    })
                    ->badge()
                    ->color(fn (string $state = null): string => match ($state) {
                        'Epic' => 'info',
                        'Legend' => 'warning',
                        'Mythic' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('description')
                    ->label('스킬 설명')
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('생성일')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('수정일')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
