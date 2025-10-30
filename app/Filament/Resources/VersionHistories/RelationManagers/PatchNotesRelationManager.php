<?php

namespace App\Filament\Resources\VersionHistories\RelationManagers;

use App\Models\Character;
use App\Models\Equipment;
use App\Models\GameTrait;
use App\Models\TacticalSkill;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PatchNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'patchNotes';

    protected static ?string $title = '패치노트';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('category')
                    ->label('구분')
                    ->options([
                        '캐릭터' => '캐릭터',
                        '특성' => '특성',
                        '아이템' => '아이템',
                        '시스템' => '시스템',
                        '전술스킬' => '전술스킬',
                        '기타' => '기타',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Set $set) => $set('target_id', null)),
                Forms\Components\Select::make('patch_type')
                    ->label('패치 유형')
                    ->options([
                        '버프' => '버프',
                        '너프' => '너프',
                        '조정' => '조정',
                        '리워크' => '리워크',
                        '신규' => '신규',
                        '삭제' => '삭제',
                    ])
                    ->required(),
                Forms\Components\Select::make('target_id')
                    ->label('대상')
                    ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                        $category = $get('category');

                        return match ($category) {
                            '캐릭터' => Character::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray(),
                            '아이템' => Equipment::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray(),
                            '특성' => GameTrait::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray(),
                            '전술스킬' => TacticalSkill::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray(),
                            default => [],
                        };
                    })
                    ->searchable()
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('category'), ['캐릭터', '아이템', '특성', '전술스킬'])),
                Forms\Components\Select::make('weapon_type')
                    ->label('무기')
                    ->placeholder('선택 안함')
                    ->options([
                        '단검' => '단검',
                        '톤파' => '톤파',
                        '활' => '활',
                        '석궁' => '석궁',
                        '글러브' => '글러브',
                        '쌍검' => '쌍검',
                        '투척' => '투척',
                        '양손검' => '양손검',
                        '도끼' => '도끼',
                        '창' => '창',
                        '망치' => '망치',
                        '채찍' => '채찍',
                        '암기' => '암기',
                        '레이피어' => '레이피어',
                        '기타' => '기타',
                        '쌍절곤' => '쌍절곤',
                        '권총' => '권총',
                        '돌격소총' => '돌격소총',
                        '저격총' => '저격총',
                        '카메라' => '카메라',
                        'VF의수' => 'VF의수',
                        '아르카나' => '아르카나',
                    ])
                    ->searchable()
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('category') === '캐릭터'),
                Forms\Components\Select::make('skill_type')
                    ->label('스킬')
                    ->placeholder('선택 안함')
                    ->options([
                        'Q' => 'Q',
                        'W' => 'W',
                        'E' => 'E',
                        'R' => 'R',
                        'T' => 'T(패시브)',
                        '기본' => '기본',
                        '패시브' => '고유 패시브',
                    ])
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('category') === '캐릭터'),
                Forms\Components\Textarea::make('content')
                    ->label('패치 내용')
                    ->required()
                    ->rows(5),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('구분')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '캐릭터' => 'info',
                        '아이템' => 'warning',
                        '특성' => 'success',
                        '시스템' => 'gray',
                        '전술스킬' => 'purple',
                        '기타' => 'gray',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('target_name')
                    ->label('대상')
                    ->getStateUsing(function ($record) {
                        if (!$record->target_id) {
                            return '-';
                        }

                        return match ($record->category) {
                            '캐릭터' => Character::find($record->target_id)?->name ?? '-',
                            '아이템' => Equipment::find($record->target_id)?->name ?? '-',
                            '특성' => GameTrait::find($record->target_id)?->name ?? '-',
                            '전술스킬' => TacticalSkill::find($record->target_id)?->name ?? '-',
                            default => '-',
                        };
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('weapon_type')
                    ->label('무기')
                    ->badge()
                    ->color('info')
                    ->default('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('skill_type')
                    ->label('스킬')
                    ->badge()
                    ->color('success')
                    ->default('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('patch_type')
                    ->label('패치 유형')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '버프' => 'success',
                        '너프' => 'danger',
                        '조정' => 'warning',
                        '리워크' => 'info',
                        '신규' => 'success',
                        '삭제' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('content')
                    ->label('패치 내용')
                    ->html()
                    ->limit(100)
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('작성일')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('구분')
                    ->options([
                        '캐릭터' => '캐릭터',
                        '특성' => '특성',
                        '아이템' => '아이템',
                        '시스템' => '시스템',
                        '전술스킬' => '전술스킬',
                        '기타' => '기타',
                    ]),

                Tables\Filters\SelectFilter::make('patch_type')
                    ->label('패치 유형')
                    ->options([
                        '버프' => '버프',
                        '너프' => '너프',
                        '조정' => '조정',
                        '리워크' => '리워크',
                        '신규' => '신규',
                        '삭제' => '삭제',
                    ]),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->label('패치노트 추가'),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
