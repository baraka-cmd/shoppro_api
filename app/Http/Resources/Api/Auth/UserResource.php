<?php

namespace App\Http\Resources\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transformer la ressource en tableau
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'avatar' => $this->avatar_url,
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),
            
            // Profil détaillé
            'profile' => [
                'first_name' => $this->profile?->first_name,
                'last_name' => $this->profile?->last_name,
                'phone' => $this->profile?->phone,
                'birth_date' => $this->profile?->birth_date?->format('Y-m-d'),
                'gender' => $this->profile?->gender,
                'country' => $this->profile?->country,
                'city' => $this->profile?->city,
                'address' => $this->profile?->address,
                'language' => $this->profile?->language ?? 'fr',
                'currency' => $this->profile?->currency ?? 'FCFA',
                'newsletter_subscribed' => $this->profile?->newsletter_subscribed ?? true,
                'sms_notifications' => $this->profile?->sms_notifications ?? true,
                'push_notifications' => $this->profile?->push_notifications ?? true,
            ],
            
            // Rôles
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => $role->display_name,
                    ];
                });
            }),
            
            // Statistiques
            'stats' => [
                'total_orders' => $this->profile?->total_orders ?? 0,
                'total_spent' => $this->profile?->total_spent ?? 0,
                'favorites_count' => $this->when(isset($this->favorites_count), $this->favorites_count),
                'reviews_count' => $this->when(isset($this->reviews_count), $this->reviews_count),
            ],
            
            // Statut du compte
            'is_active' => $this->is_active ?? true,
            'is_banned' => $this->is_banned ?? false,
            'is_suspended' => $this->is_suspended ?? false,
            'is_verified' => $this->email_verified_at !== null,
            
            // Dates
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Ajouter des métadonnées à la réponse
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}