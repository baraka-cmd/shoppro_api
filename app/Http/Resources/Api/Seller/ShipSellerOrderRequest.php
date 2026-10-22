<?php

namespace App\Http\Requests\Api\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request : ShipSellerOrderRequest
 *
 * Valide l'expédition d'un colis par le vendeur.
 *
 * Rôle métier :
 *   - Le vendeur enregistre le numéro de suivi et le transporteur
 *   - Le système passe l'item en `seller_status = 'shipped'`
 *   - Le client reçoit une notification avec le numéro de suivi
 *
 * Sécurité :
 *   - Le vendeur ne peut expédier QUE ses items (via authorize())
 *   - Le numéro de suivi doit être fourni et plausible
 *   - Le transporteur doit être dans la whitelist
 *
 * @package App\Http\Requests\Api\Seller
 */
class ShipSellerOrderRequest extends FormRequest
{
    /**
     * Liste des transporteurs acceptés (whitelist).
     * Évite les injections de valeurs arbitraires côté client.
     *
     * @var array<string>
     */
    public const ALLOWED_CARRIERS = [
        'dhl',
        'fedex',
        'ups',
        'chronopost',
        'colissimo',
        'la_poste',
        'mtn_delivery',
        'orange_delivery',
        'cameroon_express',
        'local_courier',
        'pickup_station',
        'other',
    ];

    /**
     * Autorise la requête.
     *
     * Vérifie :
     *   - L'utilisateur est un vendeur authentifié
     *   - La commande existe
     *   - La commande contient au moins 1 item du vendeur
     *   - Le vendeur peut expédier (KYC validé + compte actif)
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user || (!$user->isSeller() && !$user->isAdmin())) {
            return false;
        }

        $order = $this->route('order');

        if (!$order) {
            return false;
        }

        // Admin : peut expédier n'importe quoi (super-pouvoir)
        if ($user->isAdmin()) {
            return true;
        }

        // Vendeur : doit avoir au moins 1 item dans cette commande
        $hasSellerItems = $order->items()
            ->where('seller_id', $user->id)
            ->exists();

        if (!$hasSellerItems) {
            return false;
        }

        // Vendeur : KYC validé + compte actif
        $profile = $user->sellerProfile;

        if (!$profile || !$profile->is_active || !$profile->isKycVerified()) {
            return false;
        }

        // Vérifier qu'il reste des items en `preparing` ou `ready_to_ship`
        $canShipItems = $order->items()
            ->where('seller_id', $user->id)
            ->whereIn('seller_status', ['preparing', 'ready_to_ship'])
            ->exists();

        return $canShipItems;
    }

    /**
     * Règles de validation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // ============================================
            // NUMÉRO DE SUIVI (obligatoire)
            // ============================================
            'tracking_number' => [
                'required',
                'string',
                'min:5',
                'max:100',
                // Alphanumérique + tirets (formats de tracking standards)
                'regex:/^[A-Z0-9\-_]+$/i',
            ],

            // ============================================
            // TRANSPORTEUR (obligatoire, whitelist)
            // ============================================
            'carrier' => [
                'required',
                'string',
                Rule::in(self::ALLOWED_CARRIERS),
            ],

            // ============================================
            // URL DE SUIVI (optionnel)
            // ============================================
            // Permet au client de suivre le colis directement
            'tracking_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            // ============================================
            // DATE D'EXPÉDITION (optionnel)
            // ============================================
            // Si non fourni, on utilise maintenant
            'shipped_at' => [
                'nullable',
                'date',
                'date_format:Y-m-d H:i:s',
                'before_or_equal:now',
            ],

            // ============================================
            // NOTE VENDEUR (optionnel, interne)
            // ============================================
            'seller_note' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Messages personnalisés en français.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // tracking_number
            'tracking_number.required' => 'Le numéro de suivi est obligatoire.',
            'tracking_number.string'   => 'Le numéro de suivi doit être un texte.',
            'tracking_number.min'      => 'Le numéro de suivi doit contenir au moins :min caractères.',
            'tracking_number.max'      => 'Le numéro de suivi ne peut pas dépasser :max caractères.',
            'tracking_number.regex'    => 'Le numéro de suivi ne peut contenir que des lettres, chiffres, tirets et underscores.',

            // carrier
            'carrier.required' => 'Le transporteur est obligatoire.',
            'carrier.string'   => 'Le transporteur doit être un texte.',
            'carrier.in'       => 'Le transporteur sélectionné n\'est pas reconnu.',

            // tracking_url
            'tracking_url.url' => 'L\'URL de suivi est invalide.',
            'tracking_url.max' => 'L\'URL de suivi ne peut pas dépasser :max caractères.',

            // shipped_at
            'shipped_at.date'            => 'La date d\'expédition est invalide.',
            'shipped_at.date_format'     => 'La date d\'expédition doit être au format AAAA-MM-JJ HH:MM:SS.',
            'shipped_at.before_or_equal' => 'La date d\'expédition ne peut pas être dans le futur.',

            // seller_note
            'seller_note.max' => 'La note ne peut pas dépasser :max caractères.',
        ];
    }

    /**
     * Noms des attributs pour les messages d'erreur.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tracking_number' => 'numéro de suivi',
            'carrier'         => 'transporteur',
            'tracking_url'    => 'URL de suivi',
            'shipped_at'      => 'date d\'expédition',
            'seller_note'     => 'note vendeur',
        ];
    }

    /**
     * Prépare les données avant validation.
     *
     * - Uppercase automatique du numéro de suivi (format standard)
     * - Normalisation du transporteur (lowercase, trim)
     * - Génération auto de l'URL de suivi si un transporteur connu est fourni
     *   et qu'aucune URL explicite n'est donnée
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('tracking_number')) {
            $data['tracking_number'] = strtoupper(trim($this->input('tracking_number')));
        }

        if ($this->has('carrier')) {
            $data['carrier'] = strtolower(trim($this->input('carrier')));
        }

        if ($this->has('tracking_url')) {
            $url = trim($this->input('tracking_url'));
            $data['tracking_url'] = $url !== '' ? $url : null;
        }

        if ($this->has('seller_note')) {
            $note = trim($this->input('seller_note'));
            $data['seller_note'] = $note !== '' ? $note : null;
        }

        // Génération auto de l'URL de suivi si carrier connu
        if (!empty($data['carrier']) && empty($data['tracking_url'] ?? $this->input('tracking_url'))) {
            $autoUrl = $this->buildCarrierTrackingUrl(
                $data['carrier'],
                $data['tracking_number'] ?? $this->input('tracking_number')
            );

            if ($autoUrl) {
                $data['tracking_url'] = $autoUrl;
            }
        }

        $this->merge($data);
    }

    /**
     * Génère l'URL de suivi standard selon le transporteur.
     *
     * @param string $carrier
     * @param string|null $trackingNumber
     * @return string|null
     */
    private function buildCarrierTrackingUrl(string $carrier, ?string $trackingNumber): ?string
    {
        if (empty($trackingNumber)) {
            return null;
        }

        return match ($carrier) {
            'dhl'         => "https://www.dhl.com/fr-fr/home/tracking.html?tracking-id={$trackingNumber}",
            'fedex'       => "https://www.fedex.com/fedextrack/?trknbr={$trackingNumber}",
            'ups'         => "https://www.ups.com/track?loc=fr_FR&tracknum={$trackingNumber}",
            'chronopost'  => "https://www.chronopost.fr/tracking-no-cms/suivi-page?listeNumerosLT={$trackingNumber}",
            'colissimo'   => "https://www.laposte.fr/outils/suivre-vos-envois?code={$trackingNumber}",
            'la_poste'    => "https://www.laposte.fr/outils/suivre-vos-envois?code={$trackingNumber}",
            default       => null,
        };
    }
}