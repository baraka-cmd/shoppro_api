<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductAttribute;

class ProductAttributesSeeder extends Seeder
{
    /**
     * Seed des attributs produits
     */
    public function run(): void
    {
        $products = Product::all();

        foreach ($products as $product) {
            // Attributs communs
            $commonAttributes = [
                ['name' => 'Marque', 'value' => $product->brand?->name ?? 'Générique', 'position' => 1, 'is_filterable' => true],
                ['name' => 'Catégorie', 'value' => $product->category?->name ?? 'Autre', 'position' => 2, 'is_filterable' => true],
                ['name' => 'Disponibilité', 'value' => $product->stock > 0 ? 'En stock' : 'Rupture', 'position' => 3, 'is_filterable' => true],
            ];

            // Attributs spécifiques selon la catégorie
            $specificAttributes = [];
            
            if ($product->category?->slug === 'electronique') {
                $specificAttributes = [
                    ['name' => 'Garantie', 'value' => '12 mois', 'position' => 4, 'is_filterable' => false],
                    ['name' => 'Connectivité', 'value' => 'WiFi, Bluetooth, 5G', 'position' => 5, 'is_filterable' => false],
                    ['name' => 'Batterie', 'value' => '5000 mAh', 'position' => 6, 'is_filterable' => false],
                ];
            } elseif ($product->category?->slug === 'mode-vetements') {
                $specificAttributes = [
                    ['name' => 'Matière', 'value' => 'Coton, Polyester', 'position' => 4, 'is_filterable' => true],
                    ['name' => 'Entretien', 'value' => 'Lavage en machine', 'position' => 5, 'is_filterable' => false],
                ];
            } elseif ($product->category?->slug === 'beaute-sante') {
                $specificAttributes = [
                    ['name' => 'Type de peau', 'value' => 'Tous types', 'position' => 4, 'is_filterable' => true],
                    ['name' => 'Ingrédients', 'value' => 'Acide hyaluronique', 'position' => 5, 'is_filterable' => false],
                ];
            }

            $allAttributes = array_merge($commonAttributes, $specificAttributes);

            foreach ($allAttributes as $attribute) {
                ProductAttribute::create([
                    'product_id' => $product->id,
                    'name' => $attribute['name'],
                    'value' => $attribute['value'],
                    'position' => $attribute['position'],
                    'is_visible' => true,
                    'is_filterable' => $attribute['is_filterable'],
                    'is_active' => true,
                ]);
            }
        }
    }
}