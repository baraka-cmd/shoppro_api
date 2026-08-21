<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalog\Tag;

class TagsSeeder extends Seeder
{
    /**
     * Seed des tags
     */
    public function run(): void
    {
        $tags = [
            [
                'name' => 'Nouveau',
                'slug' => 'nouveau',
                'color' => '#FFFFFF',
                'background_color' => '#27AE60',
                'description' => 'Produit récemment ajouté',
                'is_active' => true,
            ],
            [
                'name' => 'Promo',
                'slug' => 'promo',
                'color' => '#FFFFFF',
                'background_color' => '#E74C3C',
                'description' => 'Produit en promotion',
                'is_active' => true,
            ],
            [
                'name' => 'Best-seller',
                'slug' => 'best-seller',
                'color' => '#FFFFFF',
                'background_color' => '#F39C12',
                'description' => 'Produit le plus vendu',
                'is_active' => true,
            ],
            [
                'name' => 'Édition limitée',
                'slug' => 'edition-limitee',
                'color' => '#FFFFFF',
                'background_color' => '#8E44AD',
                'description' => 'Produit en édition limitée',
                'is_active' => true,
            ],
            [
                'name' => 'Exclusif',
                'slug' => 'exclusif',
                'color' => '#FFFFFF',
                'background_color' => '#2C3E50',
                'description' => 'Produit exclusif',
                'is_active' => true,
            ],
            [
                'name' => 'Tendance',
                'slug' => 'tendance',
                'color' => '#FFFFFF',
                'background_color' => '#E67E22',
                'description' => 'Produit tendance',
                'is_active' => true,
            ],
            [
                'name' => 'Écologique',
                'slug' => 'ecologique',
                'color' => '#FFFFFF',
                'background_color' => '#16A085',
                'description' => 'Produit respectueux de l\'environnement',
                'is_active' => true,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'color' => '#FFFFFF',
                'background_color' => '#C0392B',
                'description' => 'Produit haut de gamme',
                'is_active' => true,
            ],
        ];

        foreach ($tags as $tag) {
            Tag::updateOrCreate(
                ['slug' => $tag['slug']],
                $tag
            );
        }
    }
}