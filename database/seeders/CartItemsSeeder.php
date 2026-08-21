<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order\Cart;
use App\Models\Catalog\Product;
use App\Models\Order\CartItem;

class CartItemsSeeder extends Seeder
{
    /**
     * Seed des éléments de panier
     */
    public function run(): void
    {
        $carts = Cart::all();
        $products = Product::take(10)->get();

        foreach ($carts as $cartIndex => $cart) {
            if ($cart->user_id === null) {
                continue; // Pas d'articles pour les paniers visiteurs
            }

            // 2-3 articles par panier
            $itemCount = rand(2, 3);
            
            for ($i = 0; $i < $itemCount; $i++) {
                $productIndex = ($cartIndex + $i) % count($products);
                $product = $products[$productIndex];
                $quantity = rand(1, 3);
                
                if ($product) {
                    CartItem::create([
                        'cart_id' => $cart->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $product->current_price,
                        'total_price' => $product->current_price * $quantity,
                        'product_name' => $product->name,
                        'product_image' => $product->main_image,
                        'product_sku' => $product->sku,
                        'is_active' => true,
                        'is_saved_for_later' => false,
                    ]);
                }
            }

            // Recalculer les totaux
            $cart->recalculateTotals();
        }
    }
}