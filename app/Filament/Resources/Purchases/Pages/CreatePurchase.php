<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Resources\Purchases\PurchaseResource;
use App\Models\InventoryLog;
use App\Models\ProductVariant;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $total = (float) ($data['total_cost'] ?? 0);
        $paid = (float) ($data['paid_amount'] ?? 0);
        $data['due_amount'] = max(0, $total - $paid);

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->record->items as $item) {
            $variant = ProductVariant::find($item->product_variant_id);
            if ($variant) {
                $previousStock = (int) $variant->stock_quantity;
                $variant->increment('stock_quantity', (int) $item->quantity);
                $newStock = $previousStock + (int) $item->quantity;

                InventoryLog::create([
                    'product_variant_id' => $variant->id,
                    'transaction_type' => 'purchase',
                    'quantity_change' => (int) $item->quantity,
                    'remaining_stock' => $newStock,
                    'reference_id' => $this->record->id,
                    'remarks' => "Stock received from Purchase Bill #{$this->record->reference_no}",
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                ]);
            }
        }
    }
}
