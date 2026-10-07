<?php

namespace App\Filament\Resources\ReferralSettings\Pages;

use App\Filament\Resources\ReferralSettings\ReferralSettingResource;
use Filament\Resources\Pages\ListRecords;

class ListReferralSettings extends ListRecords
{
    protected static string $resource = ReferralSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

