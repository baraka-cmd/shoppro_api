<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Marketing\FlashSale;
use App\Models\Catalog\Product;

class FlashSalesSeeder extends Seeder
{
    /**
     * Seed des ventes flash
     */
    public function run(): void
    {
        $products = Product::take(8)->get();

        $flashSales = [
            [
                'name' => 'Flash Sale Smartphones',
                'description' => 'Vente flash smartphones -50% pendant 24h',
                'discount_percentage' => 30,
                'maximum_discount' => 200000,
                'start_time' => now()->subHours(2),
                'end_time' => now()->addHours(22),
                'max_items_per_user' => 2,
                'total_stock_limit' => 50,
                'sold_count' => 15,
                'is_featured' => true,
                'show_countdown' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Flash Sale Mode',
                'description' => 'Vente flash vêtements -40% pendant 12h',
                'discount_percentage' => 40,
                'maximum_discount' => 30000,
                'start_time' => now()->addHours(6),
                'end_time' => now()->addHours(18),
                'max_items_per_user' => 3,
                'total_stock_limit' => 100,
                'sold_count' => 0,
                'is_featured' => false,
                'show_countdown' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Flash Sale Chaussures',
                'description' => 'Vente flash chaussures -35%',
                'discount_percentage' => 35,
                'maximum_discount' => 50000,
                'start_time' => now()->subHours(1),
                'end_time' => now()->addHours(11),
                'max_items_per_user' => 2,
                'total_stock_limit' => 80,
                'sold_count' => 25,
                'is_featured' => true,
                'show_countdown' => true,
                'is_active' => true,
            ],
        ];

        foreach ($flashSales as $flashSaleData) {
            $flashSale = FlashSale::updateOrCreate(
                ['name' => $flashSaleData['name']],
                $flashSaleData
            );

            // Attacher des produits à la vente flash
            if ($products->count() > 0) {
                $productIds = $products->random(min(4, $products->count()))->pluck('id')->toArray();
                $flashSale->products()->syncWithoutDetaching($productIds);
            }
        }
    }
}