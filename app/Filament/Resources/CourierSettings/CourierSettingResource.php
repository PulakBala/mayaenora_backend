<?php

namespace App\Filament\Resources\CourierSettings;

use App\Filament\Resources\CourierSettings\Pages\CreateCourierSetting;
use App\Filament\Resources\CourierSettings\Pages\EditCourierSetting;
use App\Filament\Resources\CourierSettings\Pages\ListCourierSettings;
use App\Filament\Resources\CourierSettings\Schemas\CourierSettingForm;
use App\Filament\Resources\CourierSettings\Tables\CourierSettingsTable;
use App\Models\CourierSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CourierSettingResource extends Resource
{
    protected static ?string $model = CourierSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Orders & Logistics';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Courier Settings';

    protected static ?string $modelLabel = 'Courier Gateway';

    protected static ?string $pluralModelLabel = 'Courier Gateways';

    public static function form(Schema $schema): Schema
    {
        return CourierSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourierSettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourierSettings::route('/'),
            'create' => CreateCourierSetting::route('/create'),
            'edit' => EditCourierSetting::route('/{record}/edit'),
        ];
    }
}

