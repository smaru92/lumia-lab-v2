<?php

namespace App\Filament\Resources\Equipment\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EquipmentSkillsRelationManager extends RelationManager
{
    protected static string $relationship = 'equipmentSkills';

    protected static ?string $title = '장비 스킬';

    protected static ?string $modelLabel = '장비 스킬';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 다대다 관계에서는 폼이 필요 없음
            ]);
    }

    public function table(Table $table): Table
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
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordSelectOptionsQuery(fn ($query) => $query)
                    ->recordTitle(function ($record) {
                        $gradeText = match($record->grade) {
                            'Epic' => '영웅',
                            'Legend' => '전설',
                            'Mythic' => '초월',
                            default => '',
                        };

                        if ($gradeText) {
                            return "{$record->id} - [{$gradeText}] {$record->name}";
                        }

                        return "{$record->id} - {$record->name}";
                    })
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                DetachBulkAction::make(),
            ]);
    }
}
