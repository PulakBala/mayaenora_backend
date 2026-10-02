<?php

namespace App\Filament\Resources\InventoryLogs\Pages;

use App\Filament\Resources\InventoryLogs\InventoryLogResource;
use App\Models\InventoryLog;
use App\Models\ProductVariant;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInventoryLog extends CreateRecord
{
    protected static string $resource = InventoryLogResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $raw = $this->form->getRawState();
        $actionDirection = $raw['action_direction'] ?? 'add';
        $quantity = abs((int) ($raw['quantity'] ?? 1));

        $quantityChange = $actionDirection === 'subtract' ? -$quantity : $quantity;

        $variant = ProductVariant::findOrFail($data['product_variant_id']);
        $previousStock = (int) $variant->stock_quantity;
        $newStock = max(0, $previousStock + $quantityChange);

        // Update the variant stock quantity in real time
        $variant->update(['stock_quantity' => $newStock]);

        // Create the inventory log entry
        return InventoryLog::create([
            'product_variant_id' => $variant->id,
            'transaction_type' => $data['transaction_type'] ?? 'adjustment',
            'quantity_change' => $quantityChange,
            'remaining_stock' => $newStock,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
