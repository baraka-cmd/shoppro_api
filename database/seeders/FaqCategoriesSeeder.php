<?php

namespace Database\Seeders;

use App\Models\Support\FaqCategory;
use Illuminate\Database\Seeder;

class FaqCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Commandes',
                'slug' => 'commandes',
                'icon' => 'shopping-bag',
                'description' => 'Questions sur les commandes, le suivi et la livraison',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Paiements',
                'slug' => 'paiements',
                'icon' => 'credit-card',
                'description' => 'Questions sur les moyens de paiement et remboursements',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Livraison',
                'slug' => 'livraison',
                'icon' => 'truck',
                'description' => 'Questions sur les délais, frais et zones de livraison',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Retours & Remboursements',
                'slug' => 'retours-remboursements',
                'icon' => 'refresh',
                'description' => 'Questions sur les retours, remboursements et garanties',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Compte & Sécurité',
                'slug' => 'compte-securite',
                'icon' => 'user',
                'description' => 'Questions sur le compte, mot de passe et données personnelles',
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            FaqCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}