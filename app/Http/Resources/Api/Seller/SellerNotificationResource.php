<?php

namespace App\Http\Resources\Api\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Resource : SellerNotificationResource
 *
 * Formate une notification vendeur.
 *
 * ⚠️ Règles de sécurité critiques :
 *   - Ne contient AUCUN token ou donnée sensible
 *   - Le payload (`data`) est contrôlé champ par champ
 *   - Les URLs d'action sont générées côté serveur
 *
 * Structure de la réponse :
 *   {
 *     "id": "uuid-...",
 *     "type": "new_order",
 *     "type_label": "Nouvelle commande",
 *     "icon": "shopping_bag",
 *     "color": "#3B82F6",
 *     "title": "Nouvelle commande reçue",
 *     "message": "Vous avez reçu une nouvelle commande #CMD-2026-123456",
 *     "is_read": false,
 *     "read_at": null,
 *     "action_url": "/seller/orders/detail",
 *     "data": {
 *       "order_id": 42,
 *       "order_number": "CMD-2026-123456",
 *       "amount": 25000,
 *       ...
 *     },
 *     "created_at": "...",
 *     "created_at_human": "il y a 5 min"
 *   }
 *
 * @package App\Http\Resources\Api\Seller
 */
class SellerNotificationResource extends JsonResource
{
    /**
     * Transforme la ressource en tableau JSON.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $notification = $this->resource;
        $attributes = $notification->getAttributes();
        $isAppNotification = array_key_exists('body', $attributes);
        $payload = $isAppNotification ? [] : ($notification->data ?? []);
        $type = $isAppNotification
            ? match ($notification->type) {
                'order' => 'new_order',
                'review' => 'new_review',
                'stock_alert' => 'low_stock',
                'payout' => 'payout_received',
                'system' => $notification->target_type === 'payout' ? 'payout_received' : 'general',
                default => (string) ($notification->type ?: 'general'),
            }
            : ($payload['type'] ?? $payload['event'] ?? 'general');

        if ($isAppNotification) {
            $targetId = $notification->target_id;
            $payload = match ($notification->target_type) {
                'order' => ['order_id' => $targetId],
                'review' => ['review_id' => $targetId],
                'product' => ['product_id' => $targetId],
                'payout' => ['payout_id' => $targetId],
                default => [],
            };
        }

        $readAt = $notification->read_at;
        $createdAt = $notification->created_at;
        $actionUrl = $isAppNotification
            ? match ($notification->target_type) {
                'order' => '/seller/orders/' . $notification->target_id,
                'review' => '/seller/reviews/' . $notification->target_id,
                'product' => '/seller/stocks?product_id=' . $notification->target_id,
                'payout' => '/seller/payouts/' . $notification->target_id,
                default => $this->getDefaultActionUrl($type),
            }
            : ($payload['action_url'] ?? $this->getDefaultActionUrl($type));

        return [
            // ============================================
            // IDENTIFIANTS
            // ============================================
            'id'   => $notification->id,
            'type' => $type,

            // ============================================
            // AFFICHAGE
            // ============================================
            'type_label' => $this->getTypeLabel($type),
            'icon'       => $this->getIcon($type),
            'color'      => $this->getColor($type),
            'title'      => $isAppNotification
                ? ($notification->title ?: $this->getDefaultTitle($type))
                : ($payload['title'] ?? $this->getDefaultTitle($type)),
            'message'    => $isAppNotification
                ? ($notification->body ?? '')
                : ($payload['message'] ?? $payload['body'] ?? ''),

            // ============================================
            // ÉTAT DE LECTURE
            // ============================================
            'is_read'      => $isAppNotification ? (bool) $notification->is_read : $readAt !== null,
            'read_at'      => $this->dateAsIso($readAt),

            // ============================================
            // ACTION
            // ============================================
            'action_url' => $actionUrl,

            // ============================================
            // PAYLOAD SPÉCIFIQUE (selon le type)
            // ============================================
            'data' => $isAppNotification ? $payload : $this->buildSafeData($type, $payload),

            // ============================================
            // HORODATAGE
            // ============================================
            'created_at'       => $this->dateAsIso($createdAt),
            'created_at_human' => $this->dateHuman($createdAt),
        ];
    }

    private function dateAsIso(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        return ($value instanceof Carbon ? $value : Carbon::parse($value))->toIso8601String();
    }

    private function dateHuman(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        return ($value instanceof Carbon ? $value : Carbon::parse($value))->diffForHumans();
    }

    // ============================================
    // TYPES & LABELS
    // ============================================

    /**
     * Label lisible du type.
     */
    private function getTypeLabel(string $type): string
    {
        return match ($type) {
            'new_order', 'order_created', 'order_status_changed'
                => 'Commande',
            'new_review', 'review_created'
                => 'Avis client',
            'low_stock', 'stock_alert', 'out_of_stock'
                => 'Stock',
            'payout_received', 'payout_processed', 'payout_completed'
                => 'Versement',
            'kyc_validated', 'kyc_verified'
                => 'KYC validé',
            'kyc_rejected'
                => 'KYC refusé',
            default => 'Notification',
        };
    }

    /**
     * Icône Material pour le type.
     */
    private function getIcon(string $type): string
    {
        return match ($type) {
            'new_order', 'order_created', 'order_status_changed'
                => 'shopping_bag',
            'new_review', 'review_created'
                => 'rate_review',
            'low_stock', 'stock_alert', 'out_of_stock'
                => 'inventory_2',
            'payout_received', 'payout_processed', 'payout_completed'
                => 'payments',
            'kyc_validated', 'kyc_verified'
                => 'verified_user',
            'kyc_rejected'
                => 'gpp_bad',
            default => 'notifications',
        };
    }

    /**
     * Couleur hexadécimale du type.
     */
    private function getColor(string $type): string
    {
        return match ($type) {
            'new_order', 'order_created', 'order_status_changed'
                => '#3B82F6',  // info (bleu)
            'new_review', 'review_created'
                => '#F59E0B',  // warning (orange)
            'low_stock', 'stock_alert', 'out_of_stock'
                => '#F59E0B',  // warning (orange)
            'payout_received', 'payout_processed', 'payout_completed'
                => '#10B981',  // success (vert)
            'kyc_validated', 'kyc_verified'
                => '#10B981',  // success (vert)
            'kyc_rejected'
                => '#EF4444',  // error (rouge)
            default => '#6B7280',  // gray
        };
    }

    /**
     * Titre par défaut si absent.
     */
    private function getDefaultTitle(string $type): string
    {
        return match ($type) {
            'new_order', 'order_created'
                => 'Nouvelle commande',
            'order_status_changed'
                => 'Statut de commande modifié',
            'new_review', 'review_created'
                => 'Nouvel avis client',
            'low_stock', 'stock_alert'
                => 'Alerte stock',
            'out_of_stock'
                => 'Rupture de stock',
            'payout_received', 'payout_processed'
                => 'Versement reçu',
            'payout_completed'
                => 'Versement complété',
            'kyc_validated', 'kyc_verified'
                => 'KYC validé',
            'kyc_rejected'
                => 'KYC refusé',
            default => 'Notification',
        };
    }

    /**
     * URL par défaut selon le type.
     */
    private function getDefaultActionUrl(string $type): string
    {
        return match ($type) {
            'new_order', 'order_created', 'order_status_changed'
                => '/seller/orders',
            'new_review', 'review_created'
                => '/seller/reviews',
            'low_stock', 'stock_alert', 'out_of_stock'
                => '/seller/stocks',
            'payout_received', 'payout_processed', 'payout_completed'
                => '/seller/payouts',
            'kyc_validated', 'kyc_verified', 'kyc_rejected'
                => '/seller/profile',
            default => '/seller/dashboard',
        };
    }

    // ============================================
    // PAYLOAD SÉCURISÉ
    // ============================================

    /**
     * Construit les données spécifiques selon le type.
     *
     * ⚠️ Seuls les champs connus sont exposés pour éviter toute fuite.
     */
    private function buildSafeData(string $type, array $data): array
    {
        return match ($type) {
            'new_order', 'order_created', 'order_status_changed'
                => $this->buildOrderData($data),
            'new_review', 'review_created'
                => $this->buildReviewData($data),
            'low_stock', 'stock_alert', 'out_of_stock'
                => $this->buildStockData($data),
            'payout_received', 'payout_processed', 'payout_completed'
                => $this->buildPayoutData($data),
            'kyc_validated', 'kyc_verified', 'kyc_rejected'
                => $this->buildKycData($data),
            default => [],
        };
    }

    /**
     * Données commande.
     */
    private function buildOrderData(array $data): array
    {
        return array_filter([
            'order_id'     => $data['order_id'] ?? null,
            'order_number' => $data['order_number'] ?? null,
            'amount'       => isset($data['amount']) ? (float) $data['amount'] : null,
            'customer_name'=> $data['customer_name'] ?? null,
            'items_count'  => isset($data['items_count']) ? (int) $data['items_count'] : null,
            'status'       => $data['status'] ?? null,
            'event'        => $data['event'] ?? null,
        ], fn ($v) => $v !== null);
    }

    /**
     * Données avis.
     */
    private function buildReviewData(array $data): array
    {
        return array_filter([
            'review_id'    => $data['review_id'] ?? null,
            'product_id'   => $data['product_id'] ?? null,
            'product_name' => $data['product_name'] ?? null,
            'rating'       => isset($data['rating']) ? (int) $data['rating'] : null,
            'customer_name'=> $data['customer_name'] ?? null,
            'content'      => $data['content'] ?? null,
        ], fn ($v) => $v !== null);
    }

    /**
     * Données stock.
     */
    private function buildStockData(array $data): array
    {
        return array_filter([
            'stock_id'          => $data['stock_id'] ?? null,
            'product_id'        => $data['product_id'] ?? null,
            'product_name'      => $data['product_name'] ?? null,
            'product_sku'       => $data['product_sku'] ?? null,
            'available_quantity'=> isset($data['available_quantity']) ? (int) $data['available_quantity'] : null,
            'min_quantity'      => isset($data['min_quantity']) ? (int) $data['min_quantity'] : null,
            'state'             => $data['state'] ?? null,
        ], fn ($v) => $v !== null);
    }

    /**
     * Données payout.
     */
    private function buildPayoutData(array $data): array
    {
        return array_filter([
            'payout_id'        => $data['payout_id'] ?? null,
            'amount'           => isset($data['amount']) ? (float) $data['amount'] : null,
            'amount_formatted' => $data['amount_formatted'] ?? null,
            'status'           => $data['status'] ?? null,
            'method'           => $data['method'] ?? null,
        ], fn ($v) => $v !== null);
    }

    /**
     * Données KYC.
     */
    private function buildKycData(array $data): array
    {
        return array_filter([
            'status'         => $data['status'] ?? null,
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ], fn ($v) => $v !== null);
    }
}
