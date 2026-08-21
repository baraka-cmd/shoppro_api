<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Marketing\Banner;

class BannersSeeder extends Seeder
{
    /**
     * Seed des bannières
     */
    public function run(): void
    {
        $banners = [
            [
                'title' => 'Soldes d\'été',
                'subtitle' => 'Jusqu\'à -50% sur tout',
                'description' => 'Profitez des soldes d\'été avec des réductions exceptionnelles',
                'image' => 'banners/summer-sale.jpg',
                'image_mobile' => 'banners/summer-sale-mobile.jpg',
                'thumbnail' => 'banners/summer-sale-thumb.jpg',
                'target_type' => 'promotion',
                'target_id' => '1',
                'position' => 'home_top',
                'sort_order' => 1,
                'start_date' => now()->subDays(10),
                'end_date' => now()->addDays(20),
                'is_active' => true,
                'is_animated' => true,
            ],
            [
                'title' => 'Nouveaux Smartphones',
                'subtitle' => 'Découvrez les derniers modèles',
                'description' => 'Les derniers smartphones Samsung, Apple et Huawei',
                'image' => 'banners/smartphones.jpg',
                'image_mobile' => 'banners/smartphones-mobile.jpg',
                'thumbnail' => 'banners/smartphones-thumb.jpg',
                'target_type' => 'category',
                'target_id' => '1',
                'position' => 'home_middle',
                'sort_order' => 2,
                'start_date' => null,
                'end_date' => null,
                'is_active' => true,
                'is_animated' => false,
            ],
            [
                'title' => 'Mode Tendance',
                'subtitle' => 'Nouvelle collection',
                'description' => 'La nouvelle collection mode est arrivée',
                'image' => 'banners/fashion.jpg',
                'image_mobile' => 'banners/fashion-mobile.jpg',
                'thumbnail' => 'banners/fashion-thumb.jpg',
                'target_type' => 'category',
                'target_id' => '2',
                'position' => 'home_bottom',
                'sort_order' => 3,
                'start_date' => null,
                'end_date' => null,
                'is_active' => true,
                'is_animated' => true,
            ],
            [
                'title' => 'Livraison Gratuite',
                'subtitle' => 'Dès 50 000 FCFA d\'achat',
                'description' => 'Profitez de la livraison gratuite',
                'image' => 'banners/free-shipping.jpg',
                'image_mobile' => 'banners/free-shipping-mobile.jpg',
                'thumbnail' => 'banners/free-shipping-thumb.jpg',
                'target_type' => 'none',
                'target_id' => null,
                'position' => 'sidebar',
                'sort_order' => 4,
                'start_date' => null,
                'end_date' => null,
                'is_active' => true,
                'is_animated' => false,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::updateOrCreate(
                ['title' => $banner['title']],
                $banner
            );
        }
    }
}