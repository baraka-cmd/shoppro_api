<?php

namespace App\Http\Resources\Api\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketMessageResource extends JsonResource
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
            'support_ticket_id' => $this->support_ticket_id,
            'user_id' => $this->user_id,
            'message' => $this->message,
            'attachment' => $this->attachment_url,
            'sender_type' => $this->sender_type,
            'sender_label' => $this->sender_label,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->format('Y-m-d H:i:s'),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                    'avatar' => $this->user?->avatar_url,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Label de l'expéditeur.
     */
    protected function getSenderLabelAttribute(): string
    {
        return match ($this->sender_type) {
            'user' => 'Vous',
            'admin' => 'Support ShopPro',
            'system' => 'Système',
            default => 'Utilisateur',
        };
    }

    /**
     * URL de la pièce jointe.
     */
    protected function getAttachmentUrlAttribute(): ?string
    {
        if (empty($this->attachment)) {
            return null;
        }

        if (str_starts_with($this->attachment, 'http')) {
            return $this->attachment;
        }

        return asset('storage/' . $this->attachment);
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