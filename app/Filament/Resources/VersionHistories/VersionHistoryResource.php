<?php

namespace App\Filament\Resources\VersionHistories;

use App\Filament\Resources\VersionHistories\Pages\CreateVersionHistory;
use App\Filament\Resources\VersionHistories\Pages\EditVersionHistory;
use App\Filament\Resources\VersionHistories\Pages\ListVersionHistories;
use App\Filament\Resources\VersionHistories\Schemas\VersionHistoryForm;
use App\Filament\Resources\VersionHistories\Tables\VersionHistoriesTable;
use App\Models\VersionHistory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VersionHistoryResource extends Resource
{
    protected static ?string $model = VersionHistory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return VersionHistoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VersionHistoriesTable::configure($table)
            ->recordUrl(fn ($record) => self::getUrl('edit', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PatchNotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVersionHistories::route('/'),
            'create' => CreateVersionHistory::route('/create'),
            'edit' => EditVersionHistory::route('/{record}/edit'),
        ];
    }
}
