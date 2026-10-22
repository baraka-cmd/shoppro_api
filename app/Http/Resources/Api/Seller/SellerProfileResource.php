<?php

namespace App\Http\Resources\Api\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource : SellerProfileResource
 *
 * Formate le profil vendeur pour l'API JSON.
 *
 * Objectif :
 *   - Exposer UNIQUEMENT les champs utiles au Flutter
 *   - Nettoyer les données sensibles (payout_account, etc.)
 *   - Fournir des URLs complètes (logo_url, banner_url)
 *   - Fournir des labels lisibles (kyc_status_label, etc.)
 *   - Fournir des formats prêts à afficher (balance_formatted, etc.)
 *   - Calculer des champs dérivés (can_publish_products, can_request_payout)
 *
 * ⚠️ Sécurité :
 *   - Ne PAS exposer `payout_account` en clair (masqué)
 *   - Ne PAS exposer `kyc_rejection_reason` si non rejected
 *   - Ne PAS exposer les relations lourdes (transactions, payouts)
 *
 * @package App\Http\Resources\Api\Seller
 */
class SellerProfileResource extends JsonResource
{
    /**
     * Transforme la ressource en tableau JSON.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->resource;

        return [
            // ============================================
            // IDENTIFIANTS
            // ============================================
            'id'         => $profile->id,
            'user_id'    => $profile->user_id,
            'shop_name'  => $profile->shop_name,
            'shop_slug'  => $profile->shop_slug,

            // ============================================
            // IDENTITÉ VISUELLE
            // ============================================
            'description' => $profile->description,
            'logo'        => $profile->logo,
            'logo_url'    => $profile->logo_url,
            'banner'      => $profile->banner,
            'banner_url'  => $profile->banner_url,

            // ============================================
            // CONTACT & LOCALISATION
            // ============================================
            'phone'   => $profile->phone,
            'email'   => $profile->email,
            'address' => $profile->address,
            'city'    => $profile->city,
            'country' => $profile->country,
            'location' => [
                'city'    => $profile->city,
                'country' => $profile->country,
                'full'    => trim(($profile->city ?? '') . ', ' . ($profile->country ?? ''), ', '),
            ],

            // ============================================
            // KYC
            // ============================================
            'kyc_status'           => $profile->kyc_status,
            'kyc_status_label'     => $profile->kyc_status_label,
            'kyc_verified_at'      => $profile->kyc_verified_at?->toIso8601String(),
            'kyc_verified_at_formatted' => $profile->kyc_verified_at
                ? $profile->kyc_verified_at->format('d/m/Y à H:i')
                : null,

            // ⚠️ On expose la raison de rejet UNIQUEMENT si le KYC est rejeté
            'kyc_rejection_reason' => $profile->kyc_status === 'rejected'
                ? $profile->kyc_rejection_reason
                : null,

            // ============================================
            // FINANCE
            // ============================================
            'commission_rate' => (float) $profile->commission_rate,

            'balance'                => (float) $profile->balance,
            'balance_formatted'      => $profile->balance_formatted,
            'pending_balance'        => (float) $profile->pending_balance,
            'pending_balance_formatted' => $profile->pending_balance_formatted,
            'total_revenue'          => (float) $profile->total_revenue,
            'total_revenue_formatted'=> $profile->total_revenue_formatted,

            // ⚠️ Compte de payout MASQUÉ (sécurité)
            'payout_method'        => $profile->payout_method,
            'payout_account_masked'=> $this->maskAccount($profile->payout_account),
            'has_payout_method'    => !empty($profile->payout_method) && !empty($profile->payout_account),

            // ============================================
            // STATISTIQUES DÉNORMALISÉES
            // ============================================
            'products_count' => (int) $profile->products_count,
            'orders_count'   => (int) $profile->orders_count,
            'sales_count'    => (int) $profile->sales_count,
            'reviews_count'  => (int) $profile->reviews_count,

            'average_rating'          => (float) $profile->average_rating,
            'average_rating_formatted'=> $profile->average_rating_formatted,

            // ============================================
            // STATUTS
            // ============================================
            'is_active'   => (bool) $profile->is_active,
            'is_featured' => (bool) $profile->is_featured,
            'is_verified' => (bool) $profile->is_verified,
            'status_label'=> $profile->status_label,

            // ============================================
            // CHAMPS DÉRIVÉS (calculés côté serveur)
            // ============================================
            'can_publish_products' => $profile->canPublishProducts(),
            'can_request_payout'   => $profile->canRequestPayout(),

            // ============================================
            // HORODATAGE
            // ============================================
            'created_at' => $profile->created_at?->toIso8601String(),
            'updated_at' => $profile->updated_at?->toIso8601String(),

            // ============================================
            // RELATIONS (conditionnelles — si chargées)
            // ============================================
            'user' => $this->whenLoaded('user', function () use ($profile) {
                return [
                    'id'     => $profile->user->id,
                    'name'   => $profile->user->name,
                    'email'  => $profile->user->email,
                    'avatar' => $profile->user->avatar_url,
                ];
            }),
        ];
    }

    /**
     * Masque un compte de payout pour la sécurité.
     *
     * Exemples :
     *   "1234567890" → "12******90"
     *   "+237650000000" → "+23********00"
     *   "user@paypal.com" → "us**@paypal.com"
     *
     * @param string|null $account
     * @return string|null
     */
    private function maskAccount(?string $account): ?string
    {
        if (empty($account)) {
            return null;
        }

        // Cas email
        if (str_contains($account, '@')) {
            [$local, $domain] = explode('@', $account, 2);

            $localLength = strlen($local);
            $visibleLocal = substr($local, 0, min(2, $localLength));

            return $visibleLocal . str_repeat('*', max(0, $localLength - 2)) . '@' . $domain;
        }

        // Cas numéro
        $length = strlen($account);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        $visible = 2;
        $prefix  = substr($account, 0, $visible);
        $suffix  = substr($account, -$visible);

        return $prefix . str_repeat('*', $length - ($visible * 2)) . $suffix;
    }
}