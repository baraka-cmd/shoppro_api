<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalog\Category;
use App\Models\Catalog\Subcategory;

class SubcategoriesSeeder extends Seeder
{
    /**
     * Seed des sous-catégories
     */
    public function run(): void
    {
        $subcategories = [
            // Électronique
            ['category' => 'electronique', 'name' => 'Smartphones', 'slug' => 'smartphones', 'position' => 1],
            ['category' => 'electronique', 'name' => 'Ordinateurs', 'slug' => 'ordinateurs', 'position' => 2],
            ['category' => 'electronique', 'name' => 'Tablettes', 'slug' => 'tablettes', 'position' => 3],
            ['category' => 'electronique', 'name' => 'Accessoires', 'slug' => 'accessoires-electronique', 'position' => 4],
            
            // Mode & Vêtements
            ['category' => 'mode-vetements', 'name' => 'Hommes', 'slug' => 'mode-homme', 'position' => 1],
            ['category' => 'mode-vetements', 'name' => 'Femmes', 'slug' => 'mode-femme', 'position' => 2],
            ['category' => 'mode-vetements', 'name' => 'Chaussures', 'slug' => 'chaussures', 'position' => 3],
            ['category' => 'mode-vetements', 'name' => 'Accessoires', 'slug' => 'accessoires-mode', 'position' => 4],
            
            // Maison & Jardin
            ['category' => 'maison-jardin', 'name' => 'Meubles', 'slug' => 'meubles', 'position' => 1],
            ['category' => 'maison-jardin', 'name' => 'Décoration', 'slug' => 'decoration', 'position' => 2],
            ['category' => 'maison-jardin', 'name' => 'Cuisine', 'slug' => 'cuisine', 'position' => 3],
            ['category' => 'maison-jardin', 'name' => 'Jardin', 'slug' => 'jardin', 'position' => 4],
            
            // Beauté & Santé
            ['category' => 'beaute-sante', 'name' => 'Soins visage', 'slug' => 'soins-visage', 'position' => 1],
            ['category' => 'beaute-sante', 'name' => 'Soins corps', 'slug' => 'soins-corps', 'position' => 2],
            ['category' => 'beaute-sante', 'name' => 'Parfums', 'slug' => 'parfums', 'position' => 3],
            ['category' => 'beaute-sante', 'name' => 'Compléments', 'slug' => 'complements', 'position' => 4],
            
            // Sport & Loisirs
            ['category' => 'sport-loisirs', 'name' => 'Fitness', 'slug' => 'fitness', 'position' => 1],
            ['category' => 'sport-loisirs', 'name' => 'Football', 'slug' => 'football', 'position' => 2],
            ['category' => 'sport-loisirs', 'name' => 'Basketball', 'slug' => 'basketball', 'position' => 3],
            ['category' => 'sport-loisirs', 'name' => 'Randonnée', 'slug' => 'randonnee', 'position' => 4],
            
            // Alimentation
            ['category' => 'alimentation', 'name' => 'Boissons', 'slug' => 'boissons', 'position' => 1],
            ['category' => 'alimentation', 'name' => 'Snacks', 'slug' => 'snacks', 'position' => 2],
            ['category' => 'alimentation', 'name' => 'Épicerie', 'slug' => 'epicerie', 'position' => 3],
            
            // Jouets & Enfants
            ['category' => 'jouets-enfants', 'name' => 'Jouets éducatifs', 'slug' => 'jouets-educatifs', 'position' => 1],
            ['category' => 'jouets-enfants', 'name' => 'Jeux vidéo', 'slug' => 'jeux-video', 'position' => 2],
            ['category' => 'jouets-enfants', 'name' => 'Peluches', 'slug' => 'peluches', 'position' => 3],
            
            // Automobile
            ['category' => 'automobile', 'name' => 'Accessoires auto', 'slug' => 'accessoires-auto', 'position' => 1],
            ['category' => 'automobile', 'name' => 'Pneus', 'slug' => 'pneus', 'position' => 2],
            ['category' => 'automobile', 'name' => 'Entretien', 'slug' => 'entretien-auto', 'position' => 3],
        ];

        foreach ($subcategories as $subcategoryData) {
            $category = Category::where('slug', $subcategoryData['category'])->first();
            
            if ($category) {
                Subcategory::updateOrCreate(
                    ['slug' => $subcategoryData['slug']],
                    [
                        'category_id' => $category->id,
                        'name' => $subcategoryData['name'],
                        'slug' => $subcategoryData['slug'],
                        'position' => $subcategoryData['position'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}