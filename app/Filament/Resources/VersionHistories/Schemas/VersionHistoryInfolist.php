<?php

namespace App\Filament\Resources\VersionHistories\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VersionHistoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('version_season')
                    ->label('시즌')
                    ->placeholder('-'),
                TextEntry::make('version_major')
                    ->label('메이저 버전')
                    ->numeric(),
                TextEntry::make('version_minor')
                    ->label('마이너 버전')
                    ->numeric(),
                TextEntry::make('start_date')
                    ->label('시작일')
                    ->date(),
                TextEntry::make('end_date')
                    ->label('종료일')
                    ->date(),
                TextEntry::make('created_at')
                    ->label('생성일')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('수정일')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
