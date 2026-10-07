<?php

namespace App\Filament\Resources\ReferralCommissions;

use App\Filament\Resources\ReferralCommissions\Pages\ListReferralCommissions;
use App\Filament\Resources\ReferralCommissions\Tables\ReferralCommissionsTable;
use App\Models\ReferralCommission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReferralCommissionResource extends Resource
{
    protected static ?string $model = ReferralCommission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing & Growth';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Earned Commissions';

    protected static ?string $modelLabel = 'Referral Commission';

    public static function table(Table $table): Table
    {
        return ReferralCommissionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferralCommissions::route('/'),
        ];
    }
}

