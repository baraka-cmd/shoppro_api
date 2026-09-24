<?php

namespace App\Http\Resources\Api\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
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
            'ticket_number' => $this->ticket_number,
            'user_id' => $this->user_id,
            'order_id' => $this->order_id,
            'subject' => $this->subject,
            'category' => $this->category,
            'category_label' => $this->category_label,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'priority' => $this->priority,
            'priority_label' => $this->priority_label,

            // Champ correspondant à support_tickets.description
            'description' => $this->description,

            'attachment' => $this->attachment_url,

            'resolved_at' => $this->resolved_at?->format('Y-m-d H:i:s'),
            'closed_at' => $this->closed_at?->format('Y-m-d H:i:s'),

            'unread_messages_count' => $this->when(
                isset($this->unread_messages_count),
                $this->unread_messages_count
            ),

            'messages_count' => $this->when(
                isset($this->messages_count),
                $this->messages_count
            ),

            'order' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order?->id,
                    'order_number' => $this->order?->order_number,
                    'total' => $this->order?->total,
                    'status' => $this->order?->status,
                ];
            }),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                    'email' => $this->user?->email,
                ];
            }),

            'messages' => TicketMessageResource::collection(
                $this->whenLoaded('messages')
            ),

            'last_message' => new TicketMessageResource(
                $this->whenLoaded('lastMessage')
            ),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Label de la catégorie.
     */
    protected function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'general' => 'Question générale',
            'order' => 'Commande',
            'payment' => 'Paiement',
            'delivery' => 'Livraison',
            'return' => 'Retour / Remboursement',
            'account' => 'Compte',
            'technical' => 'Technique',
            'other' => 'Autre',
            default => 'Question générale',
        };
    }

    /**
     * Label du statut.
     */
    protected function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open' => 'Ouvert',
            'in_progress' => 'En cours',
            'waiting_user' => 'En attente de votre réponse',
            'resolved' => 'Résolu',
            'closed' => 'Fermé',
            default => 'Ouvert',
        };
    }

    /**
     * Label de la priorité.
     */
    protected function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'Basse',
            'medium' => 'Moyenne',
            'high' => 'Haute',
            'urgent' => 'Urgente',
            default => 'Moyenne',
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