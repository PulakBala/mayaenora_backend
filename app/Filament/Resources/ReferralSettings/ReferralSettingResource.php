<?php

namespace App\Filament\Resources\ReferralSettings;

use App\Filament\Resources\ReferralSettings\Pages\EditReferralSetting;
use App\Filament\Resources\ReferralSettings\Pages\ListReferralSettings;
use App\Filament\Resources\ReferralSettings\Schemas\ReferralSettingForm;
use App\Filament\Resources\ReferralSettings\Tables\ReferralSettingsTable;
use App\Models\ReferralSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReferralSettingResource extends Resource
{
    protected static ?string $model = ReferralSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing & Growth';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Referral Settings';

    protected static ?string $modelLabel = 'Referral Setting';

    public static function form(Schema $schema): Schema
    {
        return ReferralSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReferralSettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferralSettings::route('/'),
            'edit' => EditReferralSetting::route('/{record}/edit'),
        ];
    }
}

