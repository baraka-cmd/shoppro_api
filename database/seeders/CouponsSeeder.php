<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Marketing\Coupon;

class CouponsSeeder extends Seeder
{
    /**
     * Seed des coupons
     */
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'BIENVENUE10',
                'name' => '10% de réduction - Bienvenue',
                'description' => 'Coupon de bienvenue pour les nouveaux clients',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'maximum_discount' => 10000,
                'minimum_order' => 5000,
                'usage_limit' => 1000,
                'usage_per_user' => 1,
                'usage_count' => 0,
                'start_date' => now()->subMonth(),
                'end_date' => now()->addMonths(6),
                'is_public' => true,
                'is_active' => true,
            ],
            [
                'code' => 'PROMO20',
                'name' => '20% de réduction',
                'description' => 'Promotion spéciale 20% sur tout',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'maximum_discount' => 20000,
                'minimum_order' => 20000,
                'usage_limit' => 500,
                'usage_per_user' => 2,
                'usage_count' => 0,
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
                'is_public' => true,
                'is_active' => true,
            ],
            [
                'code' => 'LIVRAISON',
                'name' => 'Livraison gratuite',
                'description' => 'Livraison gratuite sur votre commande',
                'discount_type' => 'free_shipping',
                'discount_value' => 0,
                'maximum_discount' => null,
                'minimum_order' => 30000,
                'usage_limit' => null,
                'usage_per_user' => 3,
                'usage_count' => 0,
                'start_date' => now()->subDays(15),
                'end_date' => now()->addMonths(1),
                'is_public' => true,
                'is_active' => true,
            ],
            [
                'code' => 'FIXE5000',
                'name' => '5000 FCFA de réduction',
                'description' => 'Réduction fixe de 5000 FCFA',
                'discount_type' => 'fixed',
                'discount_value' => 5000,
                'maximum_discount' => null,
                'minimum_order' => 50000,
                'usage_limit' => 200,
                'usage_per_user' => 1,
                'usage_count' => 0,
                'start_date' => now(),
                'end_date' => now()->addMonths(2),
                'is_public' => true,
                'is_active' => true,
            ],
            [
                'code' => 'VIP30',
                'name' => '30% VIP',
                'description' => 'Réduction VIP 30%',
                'discount_type' => 'percentage',
                'discount_value' => 30,
                'maximum_discount' => 50000,
                'minimum_order' => 100000,
                'usage_limit' => 100,
                'usage_per_user' => 1,
                'usage_count' => 0,
                'start_date' => now()->subWeek(),
                'end_date' => now()->addMonths(1),
                'is_public' => false,
                'is_active' => true,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                $coupon
            );
        }
    }
}