<?php

namespace App\Http\Resources\Api\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource : SellerOrderResource
 *
 * Formate une commande pour le vendeur connecté.
 *
 * ⚠️ Règles de sécurité critiques :
 *   - L'admin et le client NE DOIVENT PAS voir les mêmes données
 *   - Le vendeur ne voit QUE ses propres items (jamais ceux d'un autre vendeur)
 *   - Le total affiché = somme des items du vendeur (pas le total global)
 *   - Le client est partiellement anonymisé (nom + ville uniquement)
 *   - Les notes admin sont masquées
 *   - Les notes vendeur internes sont masquées au client
 *
 * Structure de la réponse :
 *   {
 *     "id": 42,
 *     "order_number": "CMD-20241019-ABC123",
 *     "status": "processing",              ← statut global (recalculé)
 *     "status_label": "En préparation",
 *     "payment_status": "paid",
 *     "payment_status_label": "Payée",
 *     "created_at": "...",
 *     "created_at_human": "il y a 2 heures",
 *
 *     "customer": {                         ← partiel
 *       "name": "Jean Dupont",
 *       "city": "Douala",
 *       "country": "Cameroun"
 *     },
 *
 *     "shipping_address": {                 ← adresse de livraison complète
 *       "full_name": "Jean Dupont",
 *       "phone": "+237 6 50 00 00 00",
 *       "address_line1": "Rue 12, Akwa",
 *       "address_line2": "Immeuble Rose",
 *       "city": "Douala",
 *       "state": "Littoral",
 *       "postal_code": "12345",
 *       "country": "Cameroun",
 *       "delivery_instructions": "Appeler avant"
 *     },
 *
 *     "items": [                            ← UNIQUEMENT ceux du vendeur
 *       {
 *         "id": 128,
 *         "product_id": 45,
 *         "product_name": "iPhone 15",
 *         "product_image_url": "https://...",
 *         "product_sku": "IPHONE-15-001",
 *         "quantity": 2,
 *         "unit_price": 750000,
 *         "unit_price_formatted": "750 000 FCFA",
 *         "total_price": 1500000,
 *         "total_price_formatted": "1 500 000 FCFA",
 *         "variant": { "color": "Noir", "size": "256 Go" },
 *         "seller_status": "preparing",
 *         "seller_status_label": "En préparation",
 *         "can_accept": false,
 *         "can_prepare": false,
 *         "can_ship": true,
 *         "can_cancel": true
 *       }
 *     ],
 *
 *     "seller_totals": {                    ← totaux du vendeur uniquement
 *       "subtotal": 1500000,
 *       "subtotal_formatted": "1 500 000 FCFA",
 *       "commission": 150000,
 *       "commission_formatted": "150 000 FCFA",
 *       "net_amount": 1350000,
 *       "net_amount_formatted": "1 350 000 FCFA",
 *       "items_count": 1,
 *       "quantity_total": 2
 *     },
 *
 *     "actions_summary": {                  ← badges pour l'UI
 *       "has_items_to_accept": false,
 *       "has_items_to_prepare": false,
 *       "has_items_to_ship": true,
 *       "is_fully_shipped": false
 *     }
 *   }
 *
 * @package App\Http\Resources\Api\Seller
 */
class SellerOrderResource extends JsonResource
{
    /**
     * ID du vendeur connecté (passé en paramètre pour éviter les fuites).
     */
    private int $sellerId;

    /**
     * Mode détail (true) ou liste (false).
     * En mode liste, on allège la réponse (pas d'adresse complète, moins d'items).
     */
    private bool $detailMode;

    /**
     * Constructeur custom.
     */
    public function __construct($resource, int $sellerId, bool $detailMode = false)
    {
        parent::__construct($resource);
        $this->sellerId = $sellerId;
        $this->detailMode = $detailMode;
    }

    /**
     * Transforme la ressource en tableau JSON.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $order = $this->resource;

        // Récupérer UNIQUEMENT les items du vendeur connecté
        $sellerItems = $order->items
            ->where('seller_id', $this->sellerId)
            ->values();

        // Si aucun item n'appartient à ce vendeur → commande invisible
        if ($sellerItems->isEmpty()) {
            return [
                'id' => null,
                'error' => 'Aucun article de ce vendeur dans cette commande.',
            ];
        }

        return [
            // ============================================
            // INFOS COMMANDE
            // ============================================
            'id'                  => $order->id,
            'order_number'        => $order->order_number,
            'status'              => $order->status,
            'status_label'        => $order->status_label,
            'payment_status'      => $order->payment_status,
            'payment_status_label'=> $order->payment_status_label,
            'currency'            => $order->currency ?? 'FCFA',

            // ============================================
            // DATES
            // ============================================
            'created_at'          => $order->created_at?->toIso8601String(),
            'created_at_human'    => $order->created_at?->diffForHumans(),
            'updated_at'          => $order->updated_at?->toIso8601String(),

            // ============================================
            // CLIENT (partiel pour la liste, détail sinon)
            // ============================================
            'customer' => $this->formatCustomer($order),

            // ============================================
            // ADRESSE DE LIVRAISON (détail uniquement)
            // ============================================
            'shipping_address' => $this->detailMode
                ? $this->formatShippingAddress($order)
                : null,

            // ============================================
            // ITEMS (UNIQUEMENT ceux du vendeur)
            // ============================================
            'items' => $sellerItems->map(function ($item) {
                return $this->formatItem($item);
            })->toArray(),

            // ============================================
            // TOTAUX DU VENDEUR (pas de la commande globale)
            // ============================================
            'seller_totals' => $this->buildSellerTotals($sellerItems),

            // ============================================
            // RÉSUMÉ ACTIONS (pour badges UI)
            // ============================================
            'actions_summary' => $this->buildActionsSummary($sellerItems),

            // ============================================
            // NOTE CLIENT (si fournie par le client)
            // ============================================
            'customer_note' => $order->customer_note,
        ];
    }

    // ============================================
    // FORMATAGE : CLIENT
    // ============================================

    /**
     * Informations client PARTIELLES (protection vie privée).
     *
     * Le vendeur n'a pas besoin de l'email/téléphone du client pour
     * traiter la commande. Il voit juste son nom (pour personnaliser l'envoi)
     * et sa ville (pour savoir si livraison locale).
     */
    private function formatCustomer($order): array
    {
        $customer = $order->user;
        $address = $order->shippingAddress;

        return [
            'name'    => $customer?->name ?? 'Client',
            'city'    => $address?->city ?? null,
            'country' => $address?->country ?? null,
        ];
    }

    // ============================================
    // FORMATAGE : ADRESSE DE LIVRAISON
    // ============================================

    /**
     * Adresse complète (détail uniquement).
     *
     * Le vendeur a besoin de l'adresse complète pour expédier le colis.
     * Mais on cache les champs sensibles inutiles (email du client).
     */
    private function formatShippingAddress($order): ?array
    {
        $address = $order->shippingAddress;

        if (!$address) {
            return null;
        }

        return [
            'full_name'             => $address->full_name,
            'phone'                 => $address->phone,
            'address_line1'         => $address->address_line1,
            'address_line2'         => $address->address_line2,
            'city'                  => $address->city,
            'state'                 => $address->state,
            'postal_code'           => $address->postal_code,
            'country'               => $address->country,
            'delivery_instructions' => $address->delivery_instructions,
        ];
    }

    // ============================================
    // FORMATAGE : ITEM
    // ============================================

    /**
     * Formate un item avec ses actions possibles.
     */
    private function formatItem($item): array
    {
        // Actions possibles selon le statut seller
        $canAccept = in_array($item->seller_status, ['to_accept'], true);
        $canPrepare = in_array($item->seller_status, ['accepted'], true);
        $canShip = in_array($item->seller_status, ['preparing', 'ready_to_ship'], true);
        $canCancel = in_array(
            $item->seller_status,
            ['to_accept', 'accepted', 'preparing', 'ready_to_ship'],
            true
        );

        return [
            'id'                 => $item->id,
            'product_id'         => $item->product_id,
            'product_name'       => $item->product_name,
            'product_image_url'  => $this->resolveImageUrl($item->product_image),
            'product_sku'        => $item->product_sku,
            'quantity'           => (int) $item->quantity,

            // Prix
            'unit_price'              => (float) $item->unit_price,
            'unit_price_formatted'    => $this->formatPrice($item->unit_price),
            'discount_price'          => $item->discount_price ? (float) $item->discount_price : null,
            'total_price'             => (float) $item->total_price,
            'total_price_formatted'   => $this->formatPrice($item->total_price),

            // Variante
            'variant' => $this->formatVariant($item),

            // Statut
            'seller_status'        => $item->seller_status,
            'seller_status_label'  => $this->getSellerStatusLabel($item->seller_status),
            'seller_status_color'  => $this->getSellerStatusColor($item->seller_status),

            // Actions possibles (pour l'UI Flutter)
            'can_accept' => $canAccept,
            'can_prepare'=> $canPrepare,
            'can_ship'   => $canShip,
            'can_cancel' => $canCancel,

            // Horodatage
            'accepted_at' => $item->seller_accepted_at?->toIso8601String(),
            'shipped_at'  => $item->seller_shipped_at?->toIso8601String(),
        ];
    }

    /**
     * Formate la variante (couleur, taille) si présente.
     */
    private function formatVariant($item): ?array
    {
        if (!$item->variant_color && !$item->variant_size && !$item->variant_name) {
            return null;
        }

        return [
            'name'  => $item->variant_name,
            'color' => $item->variant_color,
            'size'  => $item->variant_size,
        ];
    }

    // ============================================
    // TOTAUX VENDEUR
    // ============================================

    /**
     * Calcule les totaux UNIQUEMENT pour les items du vendeur.
     *
     * ⚠️ Ce sont les gains réels du vendeur, pas le total de la commande.
     */
    private function buildSellerTotals($sellerItems): array
    {
        $subtotal = $sellerItems->sum('total_price');
        $itemsCount = $sellerItems->count();
        $quantityTotal = $sellerItems->sum('quantity');

        // Commission vendeur (récupérée du profil vendeur)
        $seller = auth()->user();
        $commissionRate = $seller?->sellerProfile?->commission_rate ?? 10;
        $commission = $subtotal * ($commissionRate / 100);
        $netAmount = $subtotal - $commission;

        return [
            'subtotal'              => (float) $subtotal,
            'subtotal_formatted'    => $this->formatPrice($subtotal),
            'commission_rate'       => (float) $commissionRate,
            'commission'            => (float) $commission,
            'commission_formatted'  => $this->formatPrice($commission),
            'net_amount'            => (float) $netAmount,
            'net_amount_formatted'  => $this->formatPrice($netAmount),
            'items_count'           => $itemsCount,
            'quantity_total'        => $quantityTotal,
        ];
    }

    // ============================================
    // RÉSUMÉ ACTIONS
    // ============================================

    /**
     * Construit un résumé des actions pour l'UI.
     *
     * Permet au Flutter d'afficher les bons badges sans recompter côté client.
     */
    private function buildActionsSummary($sellerItems): array
    {
        $statusCounts = $sellerItems->groupBy('seller_status')->map->count();

        return [
            'has_items_to_accept'   => ($statusCounts['to_accept'] ?? 0) > 0,
            'has_items_to_prepare'  => ($statusCounts['accepted'] ?? 0) > 0,
            'has_items_to_ship'     => ($statusCounts['preparing'] ?? 0) +
                                       ($statusCounts['ready_to_ship'] ?? 0) > 0,
            'has_items_to_cancel'   => ($statusCounts['to_accept'] ?? 0) +
                                       ($statusCounts['accepted'] ?? 0) +
                                       ($statusCounts['preparing'] ?? 0) +
                                       ($statusCounts['ready_to_ship'] ?? 0) > 0,
            'is_fully_shipped'      => $sellerItems->every(
                fn ($item) => in_array($item->seller_status, ['shipped', 'delivered'], true)
            ),
            'is_fully_delivered'    => $sellerItems->every(
                fn ($item) => $item->seller_status === 'delivered'
            ),
        ];
    }

    // ============================================
    // HELPERS
    // ============================================

    /**
     * Label lisible pour `seller_status`.
     */
    private function getSellerStatusLabel(?string $status): string
    {
        return match ($status) {
            'to_accept'      => 'À accepter',
            'accepted'       => 'Acceptée',
            'preparing'      => 'En préparation',
            'ready_to_ship'  => 'Prête à expédier',
            'shipped'        => 'Expédiée',
            'delivered'      => 'Livrée',
            'cancelled'      => 'Annulée',
            'rejected'       => 'Refusée',
            default          => 'Inconnu',
        };
    }

    /**
     * Couleur hexadécimale pour `seller_status`.
     */
    private function getSellerStatusColor(?string $status): string
    {
        return match ($status) {
            'to_accept'      => '#F59E0B',  // warning
            'accepted'       => '#3B82F6',  // info
            'preparing'      => '#8B5CF6',  // violet
            'ready_to_ship'  => '#06B6D4',  // cyan
            'shipped'        => '#3B82F6',  // info
            'delivered'      => '#10B981',  // success
            'cancelled'      => '#EF4444',  // error
            'rejected'       => '#EF4444',  // error
            default          => '#9CA3AF',  // gray
        };
    }

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

    /**
     * Formate un prix en FCFA.
     */
    private function formatPrice($amount): string
    {
        return number_format((float) $amount, 0, ',', ' ') . ' FCFA';
    }
}