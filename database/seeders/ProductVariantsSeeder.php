<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductVariant;

class ProductVariantsSeeder extends Seeder
{
    public function run(): void
    {
        // Vider la table d'abord
        ProductVariant::query()->delete();
        
        // Samsung
        $samsung = Product::where('sku', 'SAM-S24U-001')->first();
        if ($samsung) {
            $variants = [
                ['color' => 'Noir', 'color_code' => '#000000', 'size' => '256GB', 'price' => 850000, 'stock' => 20],
                ['color' => 'Noir', 'color_code' => '#000000', 'size' => '512GB', 'price' => 950000, 'stock' => 15],
                ['color' => 'Titane', 'color_code' => '#C0C0C0', 'size' => '256GB', 'price' => 850000, 'stock' => 10],
                ['color' => 'Titane', 'color_code' => '#C0C0C0', 'size' => '512GB', 'price' => 950000, 'stock' => 5],
            ];

            foreach ($variants as $index => $variant) {
                ProductVariant::create([
                    'product_id' => $samsung->id,
                    'sku' => $samsung->sku . '-' . ($index + 1),
                    'name' => $samsung->name . ' ' . $variant['color'] . ' ' . $variant['size'],
                    'color' => $variant['color'],
                    'color_code' => $variant['color_code'],
                    'size' => $variant['size'],
                    'price' => $variant['price'],
                    'stock' => $variant['stock'],
                    'min_stock' => 5,
                    'position' => $index,
                    'is_default' => $index === 0,
                    'is_active' => true,
                ]);
            }
        }
       
        // Variantes pour Nike Air Jordan 1
        $nike = Product::where('sku', 'NIKE-AJ1-001')->first();
        if ($nike) {
            $variants = [
                ['color' => 'Rouge/Noir', 'color_code' => '#FF0000', 'size' => '40', 'price' => 120000, 'stock' => 10],
                ['color' => 'Rouge/Noir', 'color_code' => '#FF0000', 'size' => '42', 'price' => 120000, 'stock' => 15],
                ['color' => 'Rouge/Noir', 'color_code' => '#FF0000', 'size' => '44', 'price' => 120000, 'stock' => 12],
                ['color' => 'Bleu/Blanc', 'color_code' => '#0000FF', 'size' => '42', 'price' => 115000, 'stock' => 10],
            ];

            foreach ($variants as $index => $variant) {
                ProductVariant::create([
                    'product_id' => $nike->id,
                    'sku' => $nike->sku . '-' . ($index + 1),
                    'name' => $nike->name . ' ' . $variant['color'] . ' Taille ' . $variant['size'],
                    'color' => $variant['color'],
                    'color_code' => $variant['color_code'],
                    'size' => $variant['size'],
                    'price' => $variant['price'],
                    'stock' => $variant['stock'],
                    'min_stock' => 5,
                    'position' => $index,
                    'is_default' => $index === 0,
                    'is_active' => true,
                ]);
            }
        }
    }
}