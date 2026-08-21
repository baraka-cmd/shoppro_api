<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order\ShippingMethod;

class ShippingMethodsSeeder extends Seeder
{
    /**
     * Seed des méthodes de livraison
     */
    public function run(): void
    {
        $shippingMethods = [
            [
                'name' => 'Livraison Standard',
                'code' => 'standard',
                'description' => 'Livraison standard à domicile',
                'base_cost' => 2000,
                'cost_per_kg' => 500,
                'free_shipping_threshold' => 50000,
                'min_delivery_days' => 3,
                'max_delivery_days' => 7,
                'max_weight' => 20,
                'position' => 1,
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Livraison Express',
                'code' => 'express',
                'description' => 'Livraison rapide en 24-48h',
                'base_cost' => 5000,
                'cost_per_kg' => 1000,
                'free_shipping_threshold' => 100000,
                'min_delivery_days' => 1,
                'max_delivery_days' => 2,
                'max_weight' => 15,
                'position' => 2,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Livraison Économique',
                'code' => 'economy',
                'description' => 'Livraison économique (délai plus long)',
                'base_cost' => 1000,
                'cost_per_kg' => 250,
                'free_shipping_threshold' => 30000,
                'min_delivery_days' => 7,
                'max_delivery_days' => 14,
                'max_weight' => 30,
                'position' => 3,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Point Relais',
                'code' => 'pickup',
                'description' => 'Retrait en point relais',
                'base_cost' => 1000,
                'cost_per_kg' => 0,
                'free_shipping_threshold' => 20000,
                'min_delivery_days' => 2,
                'max_delivery_days' => 5,
                'max_weight' => 10,
                'position' => 4,
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($shippingMethods as $method) {
            ShippingMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}