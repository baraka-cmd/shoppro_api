<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Catalog\Product;
use App\Models\Review\Favorite;

class FavoritesSeeder extends Seeder
{
    /**
     * Seed des favoris
     */
    public function run(): void
    {
        $users = User::whereHas('roles', function ($q) {
            $q->where('name', 'client');
        })->get();

        $products = Product::take(8)->get();

        foreach ($users as $userIndex => $user) {
            // Chaque utilisateur a 2-4 favoris
            $favoriteCount = rand(2, 4);
            
            for ($i = 0; $i < $favoriteCount; $i++) {
                $productIndex = ($userIndex + $i) % count($products);
                $product = $products[$productIndex];
                
                if ($product) {
                    Favorite::updateOrCreate(
                        ['user_id' => $user->id, 'product_id' => $product->id],
                        [
                            'user_id' => $user->id,
                            'product_id' => $product->id,
                            'note' => 'À acheter bientôt',
                            'collection_name' => $i % 2 === 0 ? 'Liste de souhaits' : 'Idées cadeaux',
                            'price_alert' => $i % 2 === 0,
                            'price_alert_threshold' => $i % 2 === 0 ? $product->price * 0.8 : null,
                            'added_at' => now()->subDays(rand(1, 30)),
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}