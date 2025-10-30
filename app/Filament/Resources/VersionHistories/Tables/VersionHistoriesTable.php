<?php

namespace App\Filament\Resources\VersionHistories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VersionHistoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('version')
                    ->label('버전')
                    ->getStateUsing(function ($record) {
                        $version = "";
                        if ($record->version_season) {
                            $version .= "S{$record->version_season} ";
                        }
                        $version .= "{$record->version_major}.{$record->version_minor}";
                        return $version;
                    })
                    ->badge()
                    ->color('success')
                    ->size('lg')
                    ->sortable(['version_season', 'version_major', 'version_minor']),
                TextColumn::make('version_season')
                    ->label('시즌')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('version_major')
                    ->label('메이저')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('version_minor')
                    ->label('마이너')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('start_date')
                    ->label('시작일')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('종료일')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('상태')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $now = now();
                        $start = \Carbon\Carbon::parse($record->start_date);
                        $end = \Carbon\Carbon::parse($record->end_date);

                        if ($now->lt($start)) {
                            return '예정';
                        } elseif ($now->between($start, $end)) {
                            return '진행중';
                        } else {
                            return '종료';
                        }
                    })
                    ->color(fn (string $state): string => match ($state) {
                        '예정' => 'info',
                        '진행중' => 'success',
                        '종료' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('patch_notes_count')
                    ->label('패치노트 수')
                    ->counts('patchNotes')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('created_at')
                    ->label('생성일')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('수정일')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort(function ($query) {
                return $query
                    ->orderBy('version_season', 'desc')
                    ->orderBy('version_major', 'desc')
                    ->orderBy('version_minor', 'desc');
            })
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('패치노트 보기'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
