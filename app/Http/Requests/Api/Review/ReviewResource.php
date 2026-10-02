<?php

namespace App\Http\Resources\Api\Review;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => (float) $this->rating,
            'quality_rating' => $this->quality_rating !== null
                ? (float) $this->quality_rating
                : null,
            'price_rating' => $this->price_rating !== null
                ? (float) $this->price_rating
                : null,
            'shipping_rating' => $this->shipping_rating !== null
                ? (float) $this->shipping_rating
                : null,
            'title' => $this->title,
            'comment' => $this->content,
            'is_verified_purchase' => (bool) $this->is_verified_purchase,
            'is_recommended' => (bool) $this->is_recommended,
            'helpful_count' => (int) $this->helpful_count,
            'not_helpful_count' => (int) $this->not_helpful_count,
            'status' => $this->status,
            'admin_response' => $this->admin_response,
            'admin_response_at' => $this->admin_response_at?->toISOString(),

            // Utilisateur
            'user' => $this->whenLoaded('user', function () {
                $profile = $this->user->relationLoaded('profile')
                    ? $this->user->profile
                    : null;

                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar' => $this->resolveAvatarUrl($profile),
                ];
            }),

            // Images
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($img) {
                    return [
                        'id' => $img->id,
                        'url' => $img->image_path
                            ? asset('storage/' . $img->image_path)
                            : null,
                        'thumbnail' => $img->thumbnail_path
                            ? asset('storage/' . $img->thumbnail_path)
                            : null,
                        'alt_text' => $img->alt_text,
                        'position' => $img->position,
                    ];
                })->values()->toArray();
            }),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }

    private function resolveAvatarUrl($profile): ?string
    {
        if (!$profile || empty($profile->avatar)) {
            return null;
        }

        // Si déjà une URL complète
        if (str_starts_with($profile->avatar, 'http')) {
            return $profile->avatar;
        }

        return asset('storage/' . $profile->avatar);
    }
}