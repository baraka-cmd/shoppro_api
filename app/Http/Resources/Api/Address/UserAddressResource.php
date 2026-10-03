<?php

namespace App\Http\Resources\Api\Address;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
{
    /**
     * Transformer la ressource en tableau
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            // Identifiants
            'id' => $this->id,
            'user_id' => $this->user_id,

            // Destinataire
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,

            // Adresse
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,

            // Adresse complète formatée
            'full_address' => $this->buildFullAddress(),

            // Type
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),

            // Défaut
            'is_default' => (bool) $this->is_default,

            // Géolocalisation
            'latitude' => $this->latitude !== null
                ? (float) $this->latitude
                : null,
            'longitude' => $this->longitude !== null
                ? (float) $this->longitude
                : null,
            'has_coordinates' => $this->latitude !== null && $this->longitude !== null,

            // Instructions
            'delivery_instructions' => $this->delivery_instructions,

            // Statut
            'is_active' => (bool) $this->is_active,

            // Horodatage
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Ajouter des métadonnées à la réponse
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }

    // ============================================
    // MÉTHODES PRIVÉES
    // ============================================

    /**
     * Construire l'adresse complète formatée
     */
    private function buildFullAddress(): string
    {
        $parts = [];

        if ($this->address_line1) {
            $parts[] = $this->address_line1;
        }
        if ($this->address_line2) {
            $parts[] = $this->address_line2;
        }
        if ($this->city) {
            $parts[] = $this->city;
        }
        if ($this->state) {
            $parts[] = $this->state;
        }
        if ($this->postal_code) {
            $parts[] = $this->postal_code;
        }
        if ($this->country) {
            $parts[] = $this->country;
        }

        return implode(', ', $parts);
    }

    /**
     * Obtenir le libellé du type d'adresse en français
     */
    private function getTypeLabel(): string
    {
        return match ($this->type) {
            'home' => 'Domicile',
            'work' => 'Travail',
            'other' => 'Autre',
            default => 'Domicile',
        };
    }
}