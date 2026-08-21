<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Catalog\Product;
use App\Models\Review\Review;

class ReviewsSeeder extends Seeder
{
    /**
     * Seed des avis produits
     */
    public function run(): void
    {
        $users = User::whereHas('roles', function ($q) {
            $q->where('name', 'client');
        })->get();

        $products = Product::take(8)->get();

        $reviewContents = [
            'Excellent produit, je recommande vivement !',
            'Très bonne qualité, conforme à la description.',
            'Bon rapport qualité-prix, livraison rapide.',
            'Produit satisfaisant, je suis content de mon achat.',
            'Parfait, rien à redire. Merci ShopPro !',
            'Bien emballé et fonctionne parfaitement.',
            'Qualité moyenne, mais acceptable pour le prix.',
            'Super produit, je l\'utilise tous les jours.',
            'Déçu par la qualité, ne correspond pas aux photos.',
            'Très bon achat, le vendeur est sérieux.',
        ];

        $reviewTitles = [
            'Excellent',
            'Très satisfait',
            'Bon produit',
            'Recommandé',
            'Parfait',
            'Bien',
            'Moyen',
            'Super',
            'Déçu',
            'Top qualité',
        ];

        foreach ($products as $productIndex => $product) {
            // 3-7 avis par produit
            $reviewCount = rand(3, 7);
            
            for ($i = 0; $i < $reviewCount; $i++) {
                $user = $users[rand(0, count($users) - 1)];
                $rating = rand(3, 5);
                $contentIndex = rand(0, count($reviewContents) - 1);
                
                Review::updateOrCreate(
                    ['user_id' => $user->id, 'product_id' => $product->id],
                    [
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                        'rating' => $rating,
                        'quality_rating' => $rating,
                        'price_rating' => rand(3, 5),
                        'shipping_rating' => rand(3, 5),
                        'title' => $reviewTitles[$contentIndex],
                        'content' => $reviewContents[$contentIndex],
                        'is_verified_purchase' => rand(0, 1) === 1,
                        'is_recommended' => $rating >= 4,
                        'helpful_count' => rand(0, 20),
                        'not_helpful_count' => rand(0, 5),
                        'views_count' => rand(10, 200),
                        'status' => 'approved',
                        'approved_at' => now()->subDays(rand(1, 30)),
                    ]
                );
            }

            // Mettre à jour les statistiques du produit
            $product->updateStats();
        }
    }
}