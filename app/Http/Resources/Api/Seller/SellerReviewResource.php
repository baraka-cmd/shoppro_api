<?php

namespace App\Http\Resources\Api\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource : SellerReviewResource
 *
 * Formate un avis client pour le vendeur.
 *
 * ⚠️ Règles de sécurité critiques :
 *   - Le vendeur ne voit QUE les avis sur SES produits (filtre côté contrôleur)
 *   - L'email du client est masqué (vie privée)
 *   - Les champs internes admin (notes, historique) sont exclus
 *   - Les URLs complètes des images sont générées côté serveur
 *
 * Structure de la réponse :
 *   {
 *     "id": 42,
 *     "rating": 5,
 *     "rating_label": "Excellent",
 *     "title": "Superbe produit",
 *     "content": "...",
 *     "status": "pending",
 *     "status_label": "En attente",
 *     "created_at": "...",
 *     "created_at_human": "il y a 2 jours",
 *
 *     "customer": {
 *       "name": "Jean D.",
 *       "initials": "JD"
 *     },
 *
 *     "product": {
 *       "id": 12,
 *       "name": "iPhone 15 Pro",
 *       "image_url": "https://...",
 *       "sku": "IPHONE-15-001"
 *     },
 *
 *     "images": [
 *       { "id": 1, "url": "https://...", "thumbnail": "https://..." }
 *     ],
 *
 *     "is_verified_purchase": true,
 *     "helpful_count": 12,
 *     "not_helpful_count": 2,
 *     "helpful_rate": 85.7,
 *
 *     "admin_response": "...",          ← réponse actuelle du vendeur
 *     "admin_response_at": "...",
 *
 *     "can_respond": true,              ← actions possibles
 *     "can_approve": true,
 *     "can_reject": true
 *   }
 *
 * @package App\Http\Resources\Api\Seller
 */
class SellerReviewResource extends JsonResource
{
    /**
     * Transforme la ressource en tableau JSON.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $review = $this->resource;

        return [
            // ============================================
            // IDENTIFIANTS & NOTES
            // ============================================
            'id'           => $review->id,
            'rating'       => (int) $review->rating,
            'rating_label' => $this->getRatingLabel($review->rating),
            'title'        => $review->title,
            'content'      => $review->content,

            // ============================================
            // STATUT DE MODÉRATION
            // ============================================
            'status'       => $review->status,
            'status_label' => $review->status_label,
            'status_color' => $this->getStatusColor($review->status),

            // ============================================
            // DATES
            // ============================================
            'created_at'       => $review->created_at?->toIso8601String(),
            'created_at_human' => $review->created_at?->diffForHumans(),

            // ============================================
            // CLIENT (partiellement anonymisé)
            // ============================================
            'customer' => $this->formatCustomer($review),

            // ============================================
            // PRODUIT CONCERNÉ
            // ============================================
            'product' => $this->formatProduct($review),

            // ============================================
            // PHOTOS
            // ============================================
            'images' => $this->formatImages($review),

            // ============================================
            // STATISTIQUES D'UTILITÉ
            // ============================================
            'is_verified_purchase' => (bool) $review->is_verified_purchase,
            'helpful_count'        => (int) $review->helpful_count,
            'not_helpful_count'    => (int) $review->not_helpful_count,
            'helpful_rate'         => $this->calculateHelpfulRate($review),
            'helpful_rate_formatted' => $this->formatHelpfulRate($review),

            // ============================================
            // RÉPONSE VENDEUR (si existante)
            // ============================================
            'admin_response'       => $review->admin_response,
            'admin_response_at'    => $review->admin_response_at?->toIso8601String(),
            'has_admin_response'   => !empty($review->admin_response),

            // ============================================
            // ACTIONS POSSIBLES (pré-calculées pour l'UI)
            // ============================================
            'can_respond' => $this->canRespond($review),
            'can_approve' => $this->canApprove($review),
            'can_reject'  => $this->canReject($review),
            'can_mark_spam' => $this->canMarkSpam($review),
        ];
    }

    // ============================================
    // FORMATAGE : CLIENT
    // ============================================

    /**
     * Informations client PARTIELLES (protection vie privée).
     *
     * Le vendeur n'a pas besoin de l'email du client.
     * Il voit juste le nom (potentiellement raccourci) et les initiales.
     */
    private function formatCustomer($review): array
    {
        $user = $review->user;
        $fullName = $user?->name ?? 'Client anonyme';

        // Raccourcir le nom (ex: "Jean Dupont" → "Jean D.")
        $nameParts = explode(' ', trim($fullName));
        $displayName = count($nameParts) > 1
            ? $nameParts[0] . ' ' . strtoupper(substr(end($nameParts), 0, 1)) . '.'
            : $fullName;

        // Initiales
        $initials = '';
        if (count($nameParts) >= 2) {
            $initials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
        } elseif (count($nameParts) === 1 && !empty($nameParts[0])) {
            $initials = strtoupper(substr($nameParts[0], 0, 1));
        }

        return [
            'name'     => $displayName,
            'initials' => $initials ?: '?',
        ];
    }

    // ============================================
    // FORMATAGE : PRODUIT
    // ============================================

    /**
     * Informations produit (nom, image, SKU).
     */
    private function formatProduct($review): ?array
    {
        $product = $review->product;

        if (!$product) {
            return null;
        }

        return [
            'id'        => $product->id,
            'name'      => $product->name,
            'image_url' => $this->resolveImageUrl($product->main_image),
            'sku'       => $product->sku,
        ];
    }

    // ============================================
    // FORMATAGE : IMAGES
    // ============================================

    /**
     * Formate les images de l'avis (URLs complètes).
     */
    private function formatImages($review): array
    {
        if (!$review->relationLoaded('images') || $review->images->isEmpty()) {
            return [];
        }

        return $review->images->map(function ($image) {
            return [
                'id'        => $image->id,
                'url'       => $this->resolveImageUrl($image->image_path),
                'thumbnail' => $this->resolveImageUrl($image->thumbnail_path ?? $image->image_path),
                'alt_text'  => $image->alt_text,
            ];
        })->toArray();
    }

    // ============================================
    // CALCULS : UTILITÉ
    // ============================================

    /**
     * Calcule le taux d'utilité (%).
     */
    private function calculateHelpfulRate($review): float
    {
        $total = (int) $review->helpful_count + (int) $review->not_helpful_count;
        if ($total === 0) return 0;

        return round(($review->helpful_count / $total) * 100, 1);
    }

    /**
     * Formate le taux d'utilité en string.
     */
    private function formatHelpfulRate($review): string
    {
        return $this->calculateHelpfulRate($review) . '%';
    }

    // ============================================
    // ACTIONS POSSIBLES
    // ============================================

    /**
     * Peut-on répondre à cet avis ?
     * Oui si : approuvé ET pas encore de réponse (ou réponse à modifier).
     */
    private function canRespond($review): bool
    {
        return $review->status === 'approved';
    }

    /**
     * Peut-on approuver cet avis ?
     * Oui si : en attente (pending).
     */
    private function canApprove($review): bool
    {
        return $review->status === 'pending';
    }

    /**
     * Peut-on rejeter cet avis ?
     * Oui si : en attente ou approuvé (pas déjà rejeté/spam).
     */
    private function canReject($review): bool
    {
        return in_array($review->status, ['pending', 'approved'], true);
    }

    /**
     * Peut-on marquer comme spam ?
     * Oui si : pas déjà spam.
     */
    private function canMarkSpam($review): bool
    {
        return $review->status !== 'spam';
    }

    // ============================================
    // LABELS & COULEURS
    // ============================================

    /**
     * Label de la note (1-5).
     */
    private function getRatingLabel(int $rating): string
    {
        return match ($rating) {
            5 => 'Excellent',
            4 => 'Très bon',
            3 => 'Moyen',
            2 => 'Mauvais',
            1 => 'Très mauvais',
            default => 'Non noté',
        };
    }

    /**
     * Couleur hexadécimale pour le statut.
     */
    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'pending'  => '#F59E0B',  // warning
            'approved' => '#10B981',  // success
            'rejected' => '#EF4444',  // error
            'spam'     => '#8B5CF6',  // violet
            default    => '#6B7280',  // gray
        };
    }

    // ============================================
    // HELPERS
    // ============================================

    /**
     * Résout une URL d'image complète.
     */
    private function resolveImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/' . $path);
    }
}