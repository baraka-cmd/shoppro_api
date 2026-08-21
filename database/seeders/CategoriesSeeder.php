<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalog\Category;

class CategoriesSeeder extends Seeder
{
    /**
     * Seed des catégories principales
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Électronique',
                'slug' => 'electronique',
                'description' => 'Smartphones, ordinateurs, tablettes et accessoires',
                'icon' => 'devices',
                'position' => 1,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Mode & Vêtements',
                'slug' => 'mode-vetements',
                'description' => 'Vêtements, chaussures et accessoires de mode',
                'icon' => 'fashion',
                'position' => 2,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Maison & Jardin',
                'slug' => 'maison-jardin',
                'description' => 'Meubles, décoration et équipements pour la maison',
                'icon' => 'home',
                'position' => 3,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Beauté & Santé',
                'slug' => 'beaute-sante',
                'description' => 'Cosmétiques, soins et produits de santé',
                'icon' => 'spa',
                'position' => 4,
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Sport & Loisirs',
                'slug' => 'sport-loisirs',
                'description' => 'Équipements sportifs et articles de loisirs',
                'icon' => 'sports',
                'position' => 5,
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Alimentation',
                'slug' => 'alimentation',
                'description' => 'Produits alimentaires et boissons',
                'icon' => 'restaurant',
                'position' => 6,
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Jouets & Enfants',
                'slug' => 'jouets-enfants',
                'description' => 'Jouets, jeux et articles pour enfants',
                'icon' => 'toys',
                'position' => 7,
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Automobile',
                'slug' => 'automobile',
                'description' => 'Accessoires et équipements automobiles',
                'icon' => 'car',
                'position' => 8,
                'is_featured' => false,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}