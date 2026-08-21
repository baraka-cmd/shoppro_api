<?php

namespace App\Http\Resources\Api\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transformer le produit en tableau
     * 
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            
            // Prix
            'price' => $this->price,
            'price_formatted' => number_format($this->price, 0, ',', ' ') . ' FCFA',
            'discount_price' => $this->discount_price,
            'discount_price_formatted' => $this->discount_price 
                ? number_format($this->discount_price, 0, ',', ' ') . ' FCFA' 
                : null,
            'discount_percentage' => $this->discount_percentage,
            'current_price' => $this->current_price,
            'current_price_formatted' => number_format($this->current_price, 0, ',', ' ') . ' FCFA',
            
            // Stock
            'stock' => $this->stock,
            'is_in_stock' => $this->is_in_stock,
            'min_stock' => $this->min_stock,
            'stock_status' => $this->stock > 10 
                ? 'in_stock' 
                : ($this->stock > 0 ? 'low_stock' : 'out_of_stock'),
            
            // SKU
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            
            // Relations
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'description' => $this->category->description,
                    'image' => $this->category->image 
                        ? asset('storage/' . $this->category->image) 
                        : null,
                    'icon' => $this->category->icon,
                    'parent_id' => $this->category->parent_id,
                    'is_active' => $this->category->is_active,
                    'is_featured' => $this->category->is_featured,
                    'position' => $this->category->position,
                ];
            }),
            
            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                    'slug' => $this->brand->slug,
                    'description' => $this->brand->description,
                    'logo' => $this->brand->logo 
                        ? asset('storage/' . $this->brand->logo) 
                        : null,
                    'banner' => $this->brand->banner 
                        ? asset('storage/' . $this->brand->banner) 
                        : null,
                    'country' => $this->brand->country,
                    'is_active' => $this->brand->is_active,
                    'is_featured' => $this->brand->is_featured,
                ];
            }),
            
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => $image->image_path 
                            ? asset('storage/' . $image->image_path) 
                            : null,
                        'thumbnail' => $image->thumbnail_path 
                            ? asset('storage/' . $image->thumbnail_path) 
                            : null,
                        'is_primary' => $image->is_primary,
                        'position' => $image->position,
                    ];
                });
            }),
            
            // Image principale avec URL complète
            'main_image' => $this->main_image 
                ? asset('storage/' . $this->main_image) 
                : null,
            'main_image_thumbnail' => $this->main_image 
                ? asset('storage/' . $this->main_image) 
                : null,
            
            // Statistiques
            'views_count' => $this->views_count,
            'sales_count' => $this->sales_count,
            'favorites_count' => $this->favorites_count,
            'average_rating' => $this->average_rating,
            'reviews_count' => $this->reviews_count,
            
            // Badges
            'is_featured' => $this->is_featured,
            'is_new' => $this->is_new,
            'is_bestseller' => $this->is_bestseller,
            'is_on_sale' => $this->is_on_sale,
            'badges' => [
                'featured' => (bool) $this->is_featured,
                'new' => (bool) $this->is_new,
                'bestseller' => (bool) $this->is_bestseller,
                'sale' => (bool) ($this->is_on_sale && $this->discount_price),
            ],
            
            // Disponibilité
            'is_active' => $this->is_active,
            'is_available' => $this->is_available,
            'available_from' => $this->available_from?->format('Y-m-d'),
            'available_until' => $this->available_until?->format('Y-m-d'),
            
            // Dates
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Dans le tableau de retour
            'subcategory_id' => $this->subcategory_id,  // ✅ AJOUTER
            'category_id' => $this->category_id,  // ✅ AJOUTER AUSSI
        ];
    }

    /**
     * Ajouter des métadonnées
     * 
     * @param Request $request
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}