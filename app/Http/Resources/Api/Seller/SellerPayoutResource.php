<?php

namespace App\Http\Resources\Api\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource : SellerPayoutResource
 *
 * Formate un versement (payout) vendeur.
 *
 * ⚠️ Règles de sécurité critiques :
 *   - Le compte destinataire (IBAN, numéro mobile money) est MASQUÉ
 *   - Les notes admin ne sont jamais exposées
 *   - Seules les données utiles au vendeur sont exposées
 *
 * Structure de la réponse :
 *   {
 *     "id": 42,
 *     "reference": "PAYOUT-20261021-ABC123",
 *     "amount": 125000,
 *     "amount_formatted": "125 000 FCFA",
 *     "fee": 2500,
 *     "fee_formatted": "2 500 FCFA",
 *     "net_amount": 122500,
 *     "net_amount_formatted": "122 500 FCFA",
 *     "currency": "FCFA",
 *
 *     "method": "mobile_money",
 *     "method_label": "MTN Mobile Money",
 *     "method_icon": "phone_android",
 *     "account_masked": "+23******00",
 *
 *     "status": "pending",
 *     "status_label": "En attente",
 *     "status_color": "#F59E0B",
 *     "is_cancellable": true,
 *     "is_pending": true,
 *     "is_completed": false,
 *
 *     "requested_at": "...",
 *     "requested_at_human": "il y a 2h",
 *     "processed_at": null,
 *     "completed_at": null,
 *     "failed_at": null,
 *     "cancelled_at": null,
 *
 *     "note": "Demande de retrait mensuel",
 *     "external_reference": null,
 *     "failure_reason": null,
 *   }
 *
 * @package App\Http\Resources\Api\Seller
 */
class SellerPayoutResource extends JsonResource
{
    /**
     * Transforme la ressource en tableau JSON.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payout = $this->resource;

        return [
            // ============================================
            // IDENTIFIANTS
            // ============================================
            'id'        => $payout->id,
            'reference' => $payout->reference,

            // ============================================
            // MONTANTS
            // ============================================
            'amount'              => (float) $payout->amount,
            'amount_formatted'    => $this->formatPrice($payout->amount),
            'fee'                 => (float) $payout->fee,
            'fee_formatted'       => $this->formatPrice($payout->fee),
            'net_amount'          => (float) $payout->net_amount,
            'net_amount_formatted'=> $this->formatPrice($payout->net_amount),
            'currency'            => $payout->currency ?? 'FCFA',

            // ============================================
            // MÉTHODE DE PAIEMENT
            // ============================================
            'method'         => $payout->method,
            'method_label'   => $this->getMethodLabel($payout->method),
            'method_icon'    => $this->getMethodIcon($payout->method),
            'account_masked' => $this->maskAccount($payout->account),

            // ============================================
            // STATUT
            // ============================================
            'status'       => $payout->status,
            'status_label' => $this->getStatusLabel($payout->status),
            'status_color' => $this->getStatusColor($payout->status),

            // ============================================
            // ACTIONS POSSIBLES (pré-calculées)
            // ============================================
            'is_pending'    => $payout->status === 'pending',
            'is_processing' => $payout->status === 'processing',
            'is_completed'  => $payout->status === 'completed',
            'is_failed'     => $payout->status === 'failed',
            'is_cancelled'  => $payout->status === 'cancelled',
            'is_cancellable'=> $payout->status === 'pending',

            // ============================================
            // DATES
            // ============================================
            'requested_at'         => $payout->requested_at?->toIso8601String(),
            'requested_at_human'   => $payout->requested_at?->diffForHumans(),
            'processed_at'         => $payout->processed_at?->toIso8601String(),
            'processed_at_human'   => $payout->processed_at?->diffForHumans(),
            'completed_at'         => $payout->completed_at?->toIso8601String(),
            'completed_at_human'   => $payout->completed_at?->diffForHumans(),
            'failed_at'            => $payout->failed_at?->toIso8601String(),
            'cancelled_at'         => $payout->cancelled_at?->toIso8601String(),

            // ============================================
            // MÉTADONNÉES (sécurisées)
            // ============================================
            'note'               => $payout->note,
            'external_reference' => $payout->external_reference,
            'external_provider'  => $payout->external_provider,
            'failure_reason'     => $payout->failure_reason,

            // ⚠️ admin_note est MASQUÉ au vendeur (interne)
            // ⚠️ processed_by est MASQUÉ (interne)
        ];
    }

    // ============================================
    // MASQUAGE DU COMPTE
    // ============================================

    /**
     * Masque un compte destinataire (IBAN, mobile money, email).
     *
     * Exemples :
     *   "1234567890" → "12******90"
     *   "+237650000000" → "+23********00"
     *   "user@paypal.com" → "us**@paypal.com"
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

            return $visibleLocal
                . str_repeat('*', max(0, $localLength - 2))
                . '@' . $domain;
        }

        // Cas numéro / IBAN
        $length = strlen($account);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        $visible = 2;
        $prefix = substr($account, 0, $visible);
        $suffix = substr($account, -$visible);

        return $prefix
            . str_repeat('*', $length - ($visible * 2))
            . $suffix;
    }

    // ============================================
    // LABELS ET ICÔNES
    // ============================================

    /**
     * Label lisible de la méthode.
     */
    private function getMethodLabel(?string $method): string
    {
        return match ($method) {
            'mobile_money', 'mtn_mobile_money', 'orange_money'
                => 'Mobile Money',
            'bank', 'bank_transfer'
                => 'Virement bancaire',
            'paypal'
                => 'PayPal',
            'cash'
                => 'Espèces',
            default
                => 'Autre',
        };
    }

    /**
     * Icône Material pour la méthode.
     */
    private function getMethodIcon(?string $method): string
    {
        return match ($method) {
            'mobile_money', 'mtn_mobile_money', 'orange_money'
                => 'phone_android',
            'bank', 'bank_transfer'
                => 'account_balance',
            'paypal'
                => 'paypal',
            'cash'
                => 'payments',
            default
                => 'payment',
        };
    }

    /**
     * Label lisible du statut.
     */
    private function getStatusLabel(?string $status): string
    {
        return match ($status) {
            'pending'    => 'En attente',
            'processing' => 'En cours',
            'completed'  => 'Complété',
            'failed'     => 'Échoué',
            'cancelled'  => 'Annulé',
            default      => 'Inconnu',
        };
    }

    /**
     * Couleur hexadécimale du statut.
     */
    private function getStatusColor(?string $status): string
    {
        return match ($status) {
            'pending'    => '#F59E0B',  // warning (orange)
            'processing' => '#3B82F6',  // info (bleu)
            'completed'  => '#10B981',  // success (vert)
            'failed'     => '#EF4444',  // error (rouge)
            'cancelled'  => '#6B7280',  // gray (gris)
            default      => '#6B7280',
        };
    }

    // ============================================
    // FORMATAGE
    // ============================================

    /**
     * Formate un montant en FCFA.
     */
    private function formatPrice($amount): string
    {
        return number_format((float) $amount, 0, ',', ' ') . ' FCFA';
    }
}