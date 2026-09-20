<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $product = $this->record;
        if ($product->images()->count() > 0 && ! $product->images()->where('is_primary', true)->exists()) {
            $product->images()->first()->update(['is_primary' => true]);
        }
    }
}
