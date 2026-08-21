<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order\Order;
use App\Models\Catalog\Product;
use App\Models\Order\OrderItem;

class OrderItemsSeeder extends Seeder
{
    /**
     * Seed des éléments de commande
     */
    public function run(): void
    {
        $orders = Order::all();
        $products = Product::take(10)->get();

        foreach ($orders as $orderIndex => $order) {
            // 1-4 articles par commande
            $itemCount = rand(1, 4);
            
            for ($i = 0; $i < $itemCount; $i++) {
                $productIndex = ($orderIndex + $i) % count($products);
                $product = $products[$productIndex];
                $quantity = rand(1, 5);
                
                if ($product) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $product->current_price,
                        'total_price' => $product->current_price * $quantity,
                        'product_name' => $product->name,
                        'product_image' => $product->main_image,
                        'product_sku' => $product->sku,
                        'item_status' => $order->status === 'delivered' ? 'delivered' : 'pending',
                    ]);
                }
            }
        }
    }
}