<?php

namespace App\Filament\Resources\VersionHistories\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VersionHistoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('version_season')
                    ->label('시즌')
                    ->numeric()
                    ->default(1),
                TextInput::make('version_major')
                    ->label('메이저 버전')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('version_minor')
                    ->label('마이너 버전')
                    ->required()
                    ->numeric()
                    ->default(0),
                DatePicker::make('start_date')
                    ->label('시작일')
                    ->required(),
                DatePicker::make('end_date')
                    ->label('종료일')
                    ->required(),
            ]);
    }
}
