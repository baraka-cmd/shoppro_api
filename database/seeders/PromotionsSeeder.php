<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Marketing\Promotion;
use App\Models\Catalog\Product;

class PromotionsSeeder extends Seeder
{
    /**
     * Seed des promotions
     */
    public function run(): void
    {
        $products = Product::take(10)->get();

        $promotions = [
            [
                'name' => 'Soldes d\'été',
                'slug' => 'soldes-ete',
                'description' => 'Grandes soldes d\'été jusqu\'à -50%',
                'type' => 'discount',
                'discount_value' => 15,
                'discount_type' => 'percentage',
                'start_date' => now()->subDays(10),
                'end_date' => now()->addDays(20),
                'minimum_order' => 10000,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Offre groupée Tech',
                'slug' => 'offre-groupee-tech',
                'description' => 'Achetez un téléphone et obtenez -20% sur les accessoires',
                'type' => 'bundle',
                'discount_value' => 20,
                'discount_type' => 'percentage',
                'start_date' => now(),
                'end_date' => now()->addMonths(1),
                'minimum_order' => 50000,
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Promotion Mode',
                'slug' => 'promotion-mode',
                'description' => 'Jusqu\'à -30% sur la mode',
                'type' => 'discount',
                'discount_value' => 25,
                'discount_type' => 'percentage',
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(15),
                'minimum_order' => 0,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Liquidation Beauté',
                'slug' => 'liquidation-beaute',
                'description' => 'Liquidation des produits de beauté',
                'type' => 'clearance',
                'discount_value' => 40,
                'discount_type' => 'percentage',
                'start_date' => now()->subDays(3),
                'end_date' => now()->addDays(7),
                'minimum_order' => 0,
                'is_featured' => false,
                'is_active' => true,
            ],
        ];

        foreach ($promotions as $promotionData) {
            $promotion = Promotion::updateOrCreate(
                ['slug' => $promotionData['slug']],
                $promotionData
            );

            // Attacher des produits à la promotion
            if ($products->count() > 0) {
                $productIds = $products->random(min(4, $products->count()))->pluck('id')->toArray();
                $promotion->products()->syncWithoutDetaching($productIds);
            }
        }
    }
}