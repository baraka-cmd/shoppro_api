<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalog\Product;
use App\Models\Catalog\Warehouse;
use App\Models\Catalog\StockMovement;
use App\Models\Order\Supplier;

class StockMovementsSeeder extends Seeder
{
    /**
     * Seed des mouvements de stock
     */
    public function run(): void
    {
        $warehouse = Warehouse::where('is_default', true)->first();
        $products = Product::take(10)->get();

        if (!$warehouse) {
            return;
        }

        foreach ($products as $index => $product) {
            // Mouvement d'entrée initial
            $initialStock = $product->stock + rand(10, 50);
            
            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'purchase',
                'quantity' => $initialStock,
                'quantity_before' => 0,
                'quantity_after' => $initialStock,
                'unit_price' => $product->cost_price,
                'total_price' => $product->cost_price * $initialStock,
                'reference' => 'PO-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                'note' => 'Réception initiale du stock',
            ]);

            // Mouvement d'ajustement (si stock différent)
            if ($product->stock < $initialStock) {
                $adjustment = $initialStock - $product->stock;
                
                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'type' => 'sale',
                    'quantity' => -$adjustment,
                    'quantity_before' => $initialStock,
                    'quantity_after' => $product->stock,
                    'unit_price' => $product->price,
                    'total_price' => $product->price * $adjustment,
                    'reference' => 'SALE-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                    'note' => 'Ventes enregistrées',
                ]);
            }
        }
    }
}