<?php

namespace App\Http\Resources\Api\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    /**
     * Transformer la marque en tableau
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            
            // Images
            'logo' => $this->logo_url,
            'banner' => $this->banner_url,
            
            // Contact
            'website' => $this->website,
            'email' => $this->email,
            'phone' => $this->phone,
            
            // Origine
            'country' => $this->country,
            
            // Statistiques
            'products_count' => $this->products_count,
            'average_rating' => $this->average_rating,
            'average_rating_formatted' => number_format($this->average_rating, 1),
            
            // Statut
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            
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