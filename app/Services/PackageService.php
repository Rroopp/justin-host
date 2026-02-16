<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Customer;

class PackageService
{
    /**
     * Get the price of a package for a specific customer.
     * Falls back to base price if no specific pricing exists.
     */
    public function resolvePrice(Package $package, $customerId = null)
    {
        if (!$customerId) {
            return $package->base_price;
        }

        return $package->getPriceForCustomer($customerId);
    }

    /**
     * Get the template items for a package.
     * Returns a collection of items with default quantities.
     */
    public function getTemplateItems(Package $package)
    {
        return $package->items()->with('inventory')->get()->map(function ($item) {
            return [
                'inventory_id' => $item->inventory_id,
                'name' => $item->inventory->product_name ?? 'Unknown Item',
                'code' => $item->inventory->code ?? '',
                'quantity' => $item->quantity,
                // Include current stock for reference
                'stock' => $item->inventory->quantity_in_stock ?? 0,
            ];
        });
    }
}
