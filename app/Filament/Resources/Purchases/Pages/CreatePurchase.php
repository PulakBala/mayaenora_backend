<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Resources\Purchases\PurchaseResource;
use App\Models\InventoryLog;
use App\Models\ProductVariant;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function afterCreate(): void
    {
        foreach ($this->record->items as $item) {
            $variant = ProductVariant::find($item->product_variant_id);
            if ($variant) {
                $previousStock = $variant->stock_quantity;
                $variant->increment('stock_quantity', $item->quantity);
                $newStock = $previousStock + $item->quantity;

                InventoryLog::create([
                    'product_variant_id' => $variant->id,
                    'transaction_type' => 'purchase',
                    'quantity_change' => $item->quantity,
                    'remaining_stock' => $newStock,
                    'reference_id' => $this->record->id,
                    'remarks' => "Stock received from Purchase Bill #{$this->record->invoice_number}",
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                ]);
            }
        }
    }
}
