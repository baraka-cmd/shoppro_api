<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Order\Cart;

class CartsSeeder extends Seeder
{
    /**
     * Seed des paniers
     */
    public function run(): void
    {
        $users = User::whereHas('roles', function ($q) {
            $q->where('name', 'client');
        })->get();

        foreach ($users as $index => $user) {
            // Créer un panier pour certains utilisateurs (pas tous)
            if ($index % 3 === 0) {
                Cart::create([
                    'user_id' => $user->id,
                    'session_id' => 'session_' . uniqid(),
                    'subtotal' => 0,
                    'discount' => 0,
                    'tax' => 0,
                    'shipping_fee' => 2000,
                    'total' => 2000,
                    'items_count' => 0,
                    'total_items' => 0,
                    'currency' => 'FCFA',
                    'last_activity_at' => now(),
                    'is_active' => true,
                    'is_abandoned' => false,
                    'is_converted' => false,
                ]);
            }
        }

        // Créer un panier pour les visiteurs (session)
        Cart::create([
            'user_id' => null,
            'session_id' => 'session_visitor_' . uniqid(),
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'shipping_fee' => 0,
            'total' => 0,
            'items_count' => 0,
            'total_items' => 0,
            'currency' => 'FCFA',
            'last_activity_at' => now()->subHours(2),
            'is_active' => true,
            'is_abandoned' => false,
            'is_converted' => false,
        ]);
    }
}