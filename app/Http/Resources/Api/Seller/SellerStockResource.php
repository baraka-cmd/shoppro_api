<?php

namespace App\Http\Resources\Api\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource : SellerStockResource
 *
 * Formate un stock pour le vendeur connecté.
 *
 * ⚠️ Règles de sécurité critiques :
 *   - Le vendeur ne voit QUE les stocks de SES produits
 *   - Les IDs des entrepôts sont exposés (utiles pour l'UI)
 *   - Les données sensibles (coût d'achat) sont optionnelles
 *
 * Structure de la réponse :
 *   {
 *     "id": 42,
 *     "product_id": 12,
 *     "warehouse_id": 1,
 *     "product": {
 *       "id": 12,
 *       "name": "iPhone 15 Pro",
 *       "sku": "IPHONE-15-001",
 *       "image_url": "https://...",
 *       "price": 850000,
 *       "price_formatted": "850 000 FCFA"
 *     },
 *     "warehouse": {
 *       "id": 1,
 *       "name": "Entrepôt principal",
 *       "code": "WH-001"
 *     },
 *     "quantity": 25,
 *     "reserved_quantity": 3,
 *     "available_quantity": 22,
 *     "min_quantity": 10,
 *     "max_quantity": 100,
 *     "unit_cost": 500000,
 *     "unit_cost_formatted": "500 000 FCFA",
 *     "total_value": 12500000,
 *     "total_value_formatted": "12 500 000 FCFA",
 *     "location": "Allée A, Étagère 3",
 *     "shelf": "A3",
 *     "bin": "B12",
 *
 *     "status": "low_stock",       ← 'in_stock' | 'low_stock' | 'out_of_stock' | 'overstock'
 *     "status_label": "Stock faible",
 *     "status_color": "#F59E0B",
 *
 *     "needs_restock": true,
 *     "recommended_restock": 85,
 *     "stock_percentage": 25,      ← % du stock par rapport au max
 *     "last_counted_at": "...",
 *     "expiry_date": "...",
 *     "is_active": true,
 *     "updated_at": "...",
 *     "updated_at_human": "il y a 2h"
 *   }
 *
 * @package App\Http\Resources\Api\Seller
 */
class SellerStockResource extends JsonResource
{
    /**
     * Indique si on affiche les données sensibles (coût).
     * Par défaut, on ne les montre PAS au vendeur (seulement à l'admin).
     */
    private bool $showSensitiveData;

    /**
     * Constructeur custom pour le flag.
     */
    public function __construct($resource, bool $showSensitiveData = false)
    {
        parent::__construct($resource);
        $this->showSensitiveData = $showSensitiveData;
    }

    /**
     * Transforme la ressource en tableau JSON.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $stock = $this->resource;

        return [
            // ============================================
            // IDENTIFIANTS
            // ============================================
            'id'           => $stock->id,
            'product_id'   => $stock->product_id,
            'warehouse_id' => $stock->warehouse_id,

            // ============================================
            // PRODUIT
            // ============================================
            'product' => $this->formatProduct($stock),

            // ============================================
            // ENTREPÔT
            // ============================================
            'warehouse' => $this->formatWarehouse($stock),

            // ============================================
            // QUANTITÉS
            // ============================================
            'quantity'           => (int) $stock->quantity,
            'reserved_quantity'  => (int) $stock->reserved_quantity,
            'available_quantity' => (int) $stock->available_quantity,
            'min_quantity'       => (int) $stock->min_quantity,
            'max_quantity'       => $stock->max_quantity ? (int) $stock->max_quantity : null,

            // ============================================
            // VALEUR (optionnel selon rôle)
            // ============================================
            'unit_cost' => $this->showSensitiveData && $stock->unit_cost
                ? (float) $stock->unit_cost
                : null,
            'unit_cost_formatted' => $this->showSensitiveData && $stock->unit_cost
                ? $this->formatPrice($stock->unit_cost)
                : null,
            'total_value' => $this->showSensitiveData && $stock->total_value
                ? (float) $stock->total_value
                : null,
            'total_value_formatted' => $this->showSensitiveData && $stock->total_value
                ? $this->formatPrice($stock->total_value)
                : null,

            // ============================================
            // LOCALISATION PHYSIQUE
            // ============================================
            'location'      => $stock->location,
            'shelf'         => $stock->shelf,
            'bin'           => $stock->bin,
            'full_location' => $this->buildFullLocation($stock),

            // ============================================
            // STATUT & ALERTES
            // ============================================
            'status'       => $this->computeStatus($stock),
            'status_label' => $this->getStatusLabel($stock),
            'status_color' => $this->getStatusColor($stock),

            // ============================================
            // ACTIONS RECOMMANDÉES
            // ============================================
            'needs_restock'        => $this->needsRestock($stock),
            'recommended_restock'  => $this->computeRecommendedRestock($stock),
            'stock_percentage'     => $this->computeStockPercentage($stock),

            // ============================================
            // DATES
            // ============================================
            'last_counted_at' => $stock->last_counted_at?->toDateString(),
            'expiry_date'     => $stock->expiry_date?->toDateString(),
            'is_expiring_soon' => $this->isExpiringSoon($stock),

            // ============================================
            // ÉTAT
            // ============================================
            'is_active' => (bool) $stock->is_active,

            // ============================================
            // HORODATAGE
            // ============================================
            'created_at'       => $stock->created_at?->toIso8601String(),
            'updated_at'       => $stock->updated_at?->toIso8601String(),
            'updated_at_human' => $stock->updated_at?->diffForHumans(),
        ];
    }

    // ============================================
    // FORMATAGE : PRODUIT
    // ============================================

    /**
     * Formate les infos produit.
     */
    private function formatProduct($stock): ?array
    {
        $product = $stock->product;

        if (!$product) {
            return null;
        }

        return [
            'id'              => $product->id,
            'name'            => $product->name,
            'sku'             => $product->sku,
            'image_url'       => $this->resolveImageUrl($product->main_image),
            'price'           => (float) $product->price,
            'price_formatted' => $this->formatPrice($product->price),
            'discount_price'  => $product->discount_price ? (float) $product->discount_price : null,
        ];
    }

    // ============================================
    // FORMATAGE : ENTREPÔT
    // ============================================

    /**
     * Formate les infos entrepôt.
     */
    private function formatWarehouse($stock): ?array
    {
        $warehouse = $stock->warehouse;

        if (!$warehouse) {
            return null;
        }

        return [
            'id'   => $warehouse->id,
            'name' => $warehouse->name,
            'code' => $warehouse->code,
            'city' => $warehouse->city,
        ];
    }

    // ============================================
    // STATUT
    // ============================================

    /**
     * Calcule le statut du stock.
     */
    private function computeStatus($stock): string
    {
        $available = (int) $stock->available_quantity;

        if ($available <= 0) {
            return 'out_of_stock';
        }

        if ($available <= (int) $stock->min_quantity) {
            return 'low_stock';
        }

        if ($stock->max_quantity && $available > (int) $stock->max_quantity) {
            return 'overstock';
        }

        return 'in_stock';
    }

    /**
     * Label lisible du statut.
     */
    private function getStatusLabel($stock): string
    {
        return match ($this->computeStatus($stock)) {
            'in_stock'     => 'En stock',
            'low_stock'    => 'Stock faible',
            'out_of_stock' => 'Rupture',
            'overstock'    => 'Surstock',
            default        => 'Inconnu',
        };
    }

    /**
     * Couleur hexadécimale du statut.
     */
    private function getStatusColor($stock): string
    {
        return match ($this->computeStatus($stock)) {
            'in_stock'     => '#10B981', // success
            'low_stock'    => '#F59E0B', // warning
            'out_of_stock' => '#EF4444', // error
            'overstock'    => '#3B82F6', // info
            default        => '#6B7280', // gray
        };
    }

    // ============================================
    // ACTIONS RECOMMANDÉES
    // ============================================

    /**
     * Le stock nécessite-t-il un réapprovisionnement ?
     */
    private function needsRestock($stock): bool
    {
        return $this->computeStatus($stock) === 'low_stock'
            || $this->computeStatus($stock) === 'out_of_stock';
    }

    /**
     * Quantité recommandée à commander.
     *
     * - Si max_quantity défini : max - available
     * - Sinon : (min * 3) - available (marge de sécurité)
     */
    private function computeRecommendedRestock($stock): int
    {
        $available = (int) $stock->available_quantity;
        $min = (int) $stock->min_quantity;

        if (!$this->needsRestock($stock)) {
            return 0;
        }

        if ($stock->max_quantity) {
            return max(0, (int) $stock->max_quantity - $available);
        }

        // Fallback : viser 3× le stock min
        return max(0, ($min * 3) - $available);
    }

    /**
     * Pourcentage du stock par rapport au max.
     */
    private function computeStockPercentage($stock): float
    {
        if (!$stock->max_quantity || $stock->max_quantity <= 0) {
            return 0;
        }

        return round(((int) $stock->available_quantity / (int) $stock->max_quantity) * 100, 1);
    }

    // ============================================
    // HELPERS
    // ============================================

    /**
     * Construit l'emplacement complet lisible.
     */
    private function buildFullLocation($stock): string
    {
        $parts = [];

        if ($stock->location) $parts[] = $stock->location;
        if ($stock->shelf) $parts[] = 'Étagère ' . $stock->shelf;
        if ($stock->bin) $parts[] = 'Bac ' . $stock->bin;

        return empty($parts) ? 'Non défini' : implode(' • ', $parts);
    }

    /**
     * Vérifie si le produit expire bientôt (30 jours).
     */
    private function isExpiringSoon($stock): bool
    {
        if (!$stock->expiry_date) {
            return false;
        }

        $daysUntilExpiry = now()->diffInDays($stock->expiry_date, false);

        return $daysUntilExpiry >= 0 && $daysUntilExpiry <= 30;
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