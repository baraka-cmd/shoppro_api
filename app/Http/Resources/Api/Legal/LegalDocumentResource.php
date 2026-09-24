<?php

namespace App\Http\Resources\Api\Legal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegalDocumentResource extends JsonResource
{
    /**
     * Transformer la ressource en tableau.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'content' => $this->content,
            'version' => $this->version,
            'published_at' => $this->published_at?->format('Y-m-d H:i:s'),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Label du type de document.
     */
    protected function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'terms_conditions' => 'Conditions d\'utilisation',
            'privacy_policy' => 'Politique de confidentialité',
            'refund_policy' => 'Politique de remboursement',
            'shipping_policy' => 'Politique de livraison',
            'about' => 'À propos',
            'other' => 'Autre',
            default => 'Document',
        };
    }

    /**
     * Ajouter des métadonnées à la réponse.
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