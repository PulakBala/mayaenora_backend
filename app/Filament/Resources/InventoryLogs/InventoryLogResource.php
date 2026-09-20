<?php

namespace App\Filament\Resources\InventoryLogs;

use App\Filament\Resources\InventoryLogs\Pages\ListInventoryLogs;
use App\Filament\Resources\InventoryLogs\Tables\InventoryLogsTable;
use App\Models\InventoryLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InventoryLogResource extends Resource
{
    protected static ?string $model = InventoryLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement & Inventory';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return InventoryLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryLogs::route('/'),
        ];
    }
}
