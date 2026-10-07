<?php

namespace App\Filament\Resources\ReferralSettings\Pages;

use App\Filament\Resources\ReferralSettings\ReferralSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditReferralSetting extends EditRecord
{
    protected static string $resource = ReferralSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

