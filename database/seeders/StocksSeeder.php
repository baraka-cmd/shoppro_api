<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductVariant;
use App\Models\Catalog\Warehouse;
use App\Models\Catalog\Stock;

class StocksSeeder extends Seeder
{
    /**
     * Seed des stocks
     */
    public function run(): void
    {
        $warehouses = Warehouse::all();
        $defaultWarehouse = Warehouse::where('is_default', true)->first();

        if (!$defaultWarehouse) {
            return;
        }

        // Stock pour les produits
        $products = Product::all();
        foreach ($products as $product) {
            Stock::create([
                'product_id' => $product->id,
                'warehouse_id' => $defaultWarehouse->id,
                'quantity' => $product->stock,
                'reserved_quantity' => 0,
                'available_quantity' => $product->stock,
                'min_quantity' => $product->min_stock,
                'max_quantity' => $product->max_stock,
                'location' => 'Zone A',
                'shelf' => 'S1',
                'bin' => 'B1',
                'unit_cost' => $product->cost_price,
                'total_value' => $product->cost_price * $product->stock,
                'last_counted_at' => now(),
                'is_active' => true,
            ]);
        }

        // Stock pour les variantes
        $variants = ProductVariant::all();
        foreach ($variants as $variant) {
            Stock::create([
                'product_id' => $variant->product_id,
                'warehouse_id' => $defaultWarehouse->id,
                'product_variant_id' => $variant->id,
                'quantity' => $variant->stock,
                'reserved_quantity' => 0,
                'available_quantity' => $variant->stock,
                'min_quantity' => $variant->min_stock,
                'location' => 'Zone B',
                'shelf' => 'S2',
                'bin' => 'B2',
                'unit_cost' => $variant->price * 0.7,
                'total_value' => ($variant->price * 0.7) * $variant->stock,
                'last_counted_at' => now(),
                'is_active' => true,
            ]);
        }
    }
}