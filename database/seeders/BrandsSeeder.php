<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalog\Brand;

class BrandsSeeder extends Seeder
{
    /**
     * Seed des marques
     */
    public function run(): void
    {
        $brands = [
            // Électronique
            [
                'name' => 'Samsung',
                'slug' => 'samsung',
                'description' => 'Leader mondial de l\'électronique',
                'website' => 'https://www.samsung.com',
                'country' => 'Corée du Sud',
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Apple',
                'slug' => 'apple',
                'description' => 'Marque premium d\'électronique',
                'website' => 'https://www.apple.com',
                'country' => 'États-Unis',
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Huawei',
                'slug' => 'huawei',
                'description' => 'Constructeur chinois de smartphones',
                'website' => 'https://www.huawei.com',
                'country' => 'Chine',
                'is_featured' => false,
                'is_active' => true,
            ],
            
            // Mode
            [
                'name' => 'Nike',
                'slug' => 'nike',
                'description' => 'Équipement sportif et chaussures',
                'website' => 'https://www.nike.com',
                'country' => 'États-Unis',
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Adidas',
                'slug' => 'adidas',
                'description' => 'Marque de sport mondiale',
                'website' => 'https://www.adidas.com',
                'country' => 'Allemagne',
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Zara',
                'slug' => 'zara',
                'description' => 'Mode tendance et accessible',
                'website' => 'https://www.zara.com',
                'country' => 'Espagne',
                'is_featured' => false,
                'is_active' => true,
            ],
            
            // Maison
            [
                'name' => 'IKEA',
                'slug' => 'ikea',
                'description' => 'Meubles et décoration',
                'website' => 'https://www.ikea.com',
                'country' => 'Suède',
                'is_featured' => false,
                'is_active' => true,
            ],
            
            // Beauté
            [
                'name' => 'L\'Oréal',
                'slug' => 'loreal',
                'description' => 'Leader mondial des cosmétiques',
                'website' => 'https://www.loreal.com',
                'country' => 'France',
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Dove',
                'slug' => 'dove',
                'description' => 'Soins personnels',
                'website' => 'https://www.dove.com',
                'country' => 'États-Unis',
                'is_featured' => false,
                'is_active' => true,
            ],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['slug' => $brand['slug']],
                $brand
            );
        }
    }
}