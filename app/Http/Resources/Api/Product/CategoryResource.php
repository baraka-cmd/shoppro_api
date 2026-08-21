<?php

namespace App\Http\Resources\Api\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transformer la catégorie en tableau
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            
            // Image
            'image' => $this->image_url,
            'icon' => $this->icon,
            
            // Hiérarchie
            'parent_id' => $this->parent_id,
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'subcategories' => $this->whenLoaded('subcategories', function () {
                return $this->subcategories->map(function ($subcategory) {
                    return [
                        'id' => $subcategory->id,
                        'name' => $subcategory->name,
                        'slug' => $subcategory->slug,
                    ];
                });
            }),
            
            // Statistiques
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
            'total_products_count' => $this->total_products_count,
            
            // Statut
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'position' => $this->position,
            
            // Dates
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}