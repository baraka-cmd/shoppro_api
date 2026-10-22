<?php

namespace App\Http\Resources\Api\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource : SellerProductResource
 *
 * Formate un produit pour l'API vendeur.
 *
 * ⚠️ Sécurité :
 *   - Aucune donnée sensible n'est exposée (cost_price masqué si non autorisé)
 *   - Les relations lourdes (stocks, reviews) sont optionnelles
 *   - Les URLs complètes sont générées côté serveur
 *
 * Structure de la réponse :
 *   - Infos de base : id, name, slug, status
 *   - Description : short_description, description
 *   - Prix : price, discount_price, current_price, cost_price
 *   - Stock : stock, min_stock, max_stock, stock_status
 *   - Relations : category, brand, images
 *   - Statistiques : views, sales, favorites, rating
 *   - Badges : is_featured, is_new, is_bestseller, is_on_sale
 *   - Disponibilité : is_active, is_available, available_from, available_until
 *   - Métadonnées : created_at, updated_at
 *
 * @package App\Http\Resources\Api\Seller
 */
class SellerProductResource extends JsonResource
{
    /**
     * Indique si le produit doit être formaté en détail complet ou en résumé.
     * Utilisé pour les listes (résumé) vs détail (complet).
     */
    private bool $isDetailMode = false;

    /**
     * Active le mode détail (toutes les infos).
     */
    public function withDetail(): self
    {
        $this->isDetailMode = true;
        return $this;
    }

    /**
     * Transforme la ressource en tableau JSON.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->resource;

        // ============================================
        // DONNÉES DE BASE
        // ============================================
        $data = [
            'id'                => $product->id,
            'name'              => $product->name,
            'slug'              => $product->slug,
            'short_description' => $product->short_description,
            'description'       => $this->isDetailMode ? $product->description : null,

            // ============================================
            // PRIX
            // ============================================
            'price'             => (float) $product->price,
            'price_formatted'   => $this->formatPrice($product->price),
            'discount_price'    => $product->discount_price ? (float) $product->discount_price : null,
            'discount_price_formatted' => $product->discount_price
                ? $this->formatPrice($product->discount_price)
                : null,
            'current_price'     => (float) $product->current_price,
            'current_price_formatted' => $this->formatPrice($product->current_price),
            'discount_percentage' => $product->discount_percentage,
            'has_discount'      => $product->discount_price !== null
                                    && $product->discount_price < $product->price,

            // ============================================
            // STOCK
            // ============================================
            'stock'             => (int) $product->stock,
            'min_stock'         => (int) $product->min_stock,
            'max_stock'         => $product->max_stock ? (int) $product->max_stock : null,
            'stock_status'      => $product->stock_status,
            'stock_status_label' => $this->getStockStatusLabel($product->stock_status ?? ''),
            'is_in_stock'       => $product->stock > 0,
            'is_low_stock'      => $product->stock > 0 && $product->stock <= $product->min_stock,
            'is_out_of_stock'   => $product->stock <= 0,

            // ============================================
            // IDENTIFIANTS
            // ============================================
            'sku'               => $product->sku,
            'barcode'           => $product->barcode,

            // ============================================
            // RELATIONS (uniquement les données essentielles)
            // ============================================
            'category' => $product->relationLoaded('category') && $product->category
                ? [
                    'id'    => $product->category->id,
                    'name'  => $product->category->name,
                    'slug'  => $product->category->slug,
                    'image' => $this->resolveImageUrl($product->category->image),
                ]
                : null,

            'brand' => $product->relationLoaded('brand') && $product->brand
                ? [
                    'id'    => $product->brand->id,
                    'name'  => $product->brand->name,
                    'slug'  => $product->brand->slug,
                    'logo'  => $this->resolveImageUrl($product->brand->logo),
                ]
                : null,

            'subcategory_id' => $product->subcategory_id,
            'category_id'    => $product->category_id,
            'brand_id'       => $product->brand_id,

            // ============================================
            // IMAGES
            // ============================================
            'main_image'           => $this->resolveImageUrl($product->main_image),
            'main_image_thumbnail' => $this->resolveImageUrl($product->main_image),
            'images'               => $this->formatImages($product),
            'images_count'         => $product->relationLoaded('images')
                ? $product->images->count()
                : 0,

            // ============================================
            // STATISTIQUES
            // ============================================
            'views_count'       => (int) $product->views_count,
            'sales_count'       => (int) $product->sales_count,
            'favorites_count'   => (int) $product->favorites_count,
            'reviews_count'     => (int) $product->reviews_count,
            'average_rating'    => (float) $product->average_rating,
            'average_rating_formatted' => number_format($product->average_rating, 1),

            // ============================================
            // BADGES
            // ============================================
            'is_featured'    => (bool) $product->is_featured,
            'is_new'         => (bool) $product->is_new,
            'is_bestseller'  => (bool) $product->is_bestseller,
            'is_on_sale'     => (bool) $product->is_on_sale,
            'is_digital'     => (bool) $product->is_digital,
            'badges'         => $this->buildBadges($product),

            // ============================================
            // DISPONIBILITÉ
            // ============================================
            'is_active'         => (bool) $product->is_active,
            'is_available'      => (bool) $product->is_available,
            'status_label'      => $this->getStatusLabel($product),
            'available_from'    => $product->available_from?->toDateString(),
            'available_until'   => $product->available_until?->toDateString(),

            // ============================================
            // MÉTADONNÉES (détail uniquement)
            // ============================================
            'meta_title'        => $this->isDetailMode ? $product->meta_title : null,
            'meta_description'  => $this->isDetailMode ? $product->meta_description : null,
            'meta_keywords'     => $this->isDetailMode ? $product->meta_keywords : null,

            // ============================================
            // HORODATAGE
            // ============================================
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
            'deleted_at' => $product->deleted_at?->toIso8601String(),
        ];

        // ============================================
        // DONNÉES SENSIBLES (détail + admin uniquement)
        // ============================================
        // Le cost_price n'est PAS exposé aux vendeurs normaux
        // (mais l'admin le voit)
        if ($this->isDetailMode && $request->user()?->isAdmin()) {
            $data['cost_price'] = (float) $product->cost_price;
            $data['tax_rate']   = (float) $product->tax_rate;
        }

        return $data;
    }

    // ============================================
    // HELPERS
    // ============================================

    /**
     * Formate une image en objet complet.
     */
    private function formatImages($product): array
    {
        if (!$product->relationLoaded('images')) {
            return [];
        }

        return $product->images->map(function ($image) {
            return [
                'id'           => $image->id,
                'url'          => $this->resolveImageUrl($image->image_path),
                'thumbnail'    => $this->resolveImageUrl($image->thumbnail_path ?? $image->image_path),
                'alt_text'     => $image->alt_text,
                'position'     => (int) $image->position,
                'is_primary'   => (bool) $image->is_primary,
                'width'        => $image->width,
                'height'       => $image->height,
                'file_size'    => $image->file_size,
            ];
        })->toArray();
    }

    /**
     * Construit les badges actifs.
     */
    private function buildBadges($product): array
    {
        $badges = [];

        if ($product->is_on_sale && $product->discount_price) {
            $badges[] = 'promo';
        }
        if ($product->is_new) {
            $badges[] = 'new';
        }
        if ($product->is_bestseller) {
            $badges[] = 'bestseller';
        }
        if ($product->is_featured) {
            $badges[] = 'featured';
        }

        return $badges;
    }

    /**
     * Label lisible du statut stock.
     */
    
    private function getStockStatusLabel(?string $status): string
    {
        return match ($status) {
            'in_stock'     => 'En stock',
            'low_stock'    => 'Stock faible',
            'out_of_stock' => 'Rupture de stock',
            default        => 'Inconnu',
        };
    }

    /**
     * Label lisible du statut du produit.
     */
    private function getStatusLabel($product): string
    {
        if ($product->deleted_at) {
            return 'Archivé';
        }
        if (!$product->is_active) {
            return 'Brouillon';
        }
        if (!$product->is_available) {
            return 'Indisponible';
        }
        return 'Publié';
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
     * Formate un montant en FCFA.
     */
    private function formatPrice($amount): string
    {
        return number_format((float) $amount, 0, ',', ' ') . ' FCFA';
    }
}