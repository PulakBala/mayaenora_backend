<?php

namespace App\Filament\Resources\CourierSettings\Pages;

use App\Filament\Resources\CourierSettings\CourierSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCourierSettings extends ListRecords
{
    protected static string $resource = CourierSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Configure New Courier'),
        ];
    }
}

