<?php

namespace App\Http\Requests\Api\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request : CancelSellerOrderRequest
 *
 * Valide l'annulation d'un item de commande par le vendeur.
 *
 * Rôle métier :
 *   - Le vendeur annule son article (rupture de stock, refus, erreur, etc.)
 *   - Le client est automatiquement remboursé de la valeur de l'item
 *   - Le statut global de la commande est recalculé
 *
 * Sécurité :
 *   - Le vendeur ne peut annuler QUE ses items
 *   - Un item déjà expédié NE PEUT PAS être annulé (il doit passer par un retour)
 *   - La raison doit être dans la whitelist
 *   - Un remboursement est tracé (audit comptable)
 *
 * Règles métier strictes :
 *   - Statuts annulables : to_accept, accepted, preparing, ready_to_ship
 *   - Statuts NON annulables : shipped, delivered, cancelled, rejected
 *   - Si le produit n'a plus de stock, l'annulation est automatique
 *   - L'admin peut toujours annuler (super-pouvoir)
 *
 * @package App\Http\Requests\Api\Seller
 */
class CancelSellerOrderRequest extends FormRequest
{
    /**
     * Motifs d'annulation acceptés (whitelist).
     *
     * Chaque motif a un code machine + un label FR pour l'affichage client.
     *
     * @var array<string, string>
     */
    public const ALLOWED_REASONS = [
        'out_of_stock'         => 'Produit en rupture de stock',
        'damaged'              => 'Produit endommagé',
        'customer_request'     => 'Demande du client',
        'pricing_error'        => 'Erreur de prix',
        'cannot_deliver'       => 'Zone non livrable',
        'seller_unavailable'   => 'Vendeur indisponible',
        'duplicate_order'      => 'Commande en doublon',
        'suspected_fraud'      => 'Suspicion de fraude',
        'other'                => 'Autre raison',
    ];

    /**
     * Statuts d'item qui peuvent être annulés.
     *
     * @var array<string>
     */
    private const CANCELLABLE_STATUSES = [
        'to_accept',
        'accepted',
        'preparing',
        'ready_to_ship',
    ];

    /**
     * Autorise la requête.
     *
     * Vérifie :
     *   - L'utilisateur est un vendeur authentifié
     *   - La commande existe
     *   - L'item appartient au vendeur
     *   - L'item est encore dans un statut annulable
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

        // Admin : peut annuler n'importe quoi (super-pouvoir, mais tracé)
        if ($user->isAdmin()) {
            return true;
        }

        // Vendeur : KYC validé + compte actif
        $profile = $user->sellerProfile;

        if (!$profile || !$profile->is_active || !$profile->isKycVerified()) {
            return false;
        }

        // Récupérer les items du vendeur dans cette commande
        $sellerItems = $order->items()
            ->where('seller_id', $user->id)
            ->get();

        if ($sellerItems->isEmpty()) {
            return false;
        }

        // Vérifier qu'au moins un item est annulable
        $hasCancellableItem = $sellerItems->contains(function ($item) {
            return in_array($item->seller_status, self::CANCELLABLE_STATUSES, true);
        });

        return $hasCancellableItem;
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
            // RAISON (obligatoire, whitelist)
            // ============================================
            'reason' => [
                'required',
                'string',
                Rule::in(array_keys(self::ALLOWED_REASONS)),
            ],

            // ============================================
            // DÉTAILS (optionnel mais recommandé pour 'other')
            // ============================================
            'reason_details' => [
                'nullable',
                'string',
                'max:500',
                // Obligatoire si reason = 'other'
                Rule::requiredIf(fn () => $this->input('reason') === 'other'),
            ],

            // ============================================
            // RESTOCKER (par défaut : oui)
            // ============================================
            // Indique si le stock doit être réintégré après annulation
            'restock' => [
                'nullable',
                'boolean',
            ],

            // ============================================
            // ITEMS À ANNULER (optionnel)
            // ============================================
            // Si null → tous les items annulables du vendeur
            // Si fourni → uniquement ceux de la liste
            'item_ids' => [
                'nullable',
                'array',
                'min:1',
            ],
            'item_ids.*' => [
                'integer',
                // Doit exister et appartenir à la commande
                Rule::exists('order_items', 'id')->where(function ($query) {
                    $order = $this->route('order');
                    $user = $this->user();

                    $query->where('order_id', $order->id);

                    // Filtrer par vendeur (sauf admin)
                    if ($user && !$user->isAdmin()) {
                        $query->where('seller_id', $user->id);
                    }

                    // Filtrer par statut annulable
                    $query->whereIn('seller_status', self::CANCELLABLE_STATUSES);
                }),
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
            // reason
            'reason.required' => 'La raison de l\'annulation est obligatoire.',
            'reason.string'   => 'La raison doit être un texte.',
            'reason.in'       => 'La raison sélectionnée n\'est pas valide.',

            // reason_details
            'reason_details.string'   => 'Les détails doivent être un texte.',
            'reason_details.max'      => 'Les détails ne peuvent pas dépasser :max caractères.',
            'reason_details.required' => 'Veuillez préciser la raison de l\'annulation (obligatoire pour "Autre raison").',

            // restock
            'restock.boolean' => 'Le paramètre restock doit être vrai ou faux.',

            // item_ids
            'item_ids.array'      => 'La liste des articles est invalide.',
            'item_ids.min'        => 'Veuillez sélectionner au moins un article.',
            'item_ids.*.integer'  => 'Chaque identifiant d\'article doit être un nombre.',
            'item_ids.*.exists'   => 'Un des articles sélectionnés n\'existe pas ou n\'est plus annulable.',
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
            'reason'         => 'raison',
            'reason_details' => 'détails',
            'restock'        => 'restock',
            'item_ids'       => 'articles',
            'item_ids.*'     => 'article',
        ];
    }

    /**
     * Prépare les données avant validation.
     *
     * - Valeur par défaut pour restock (true)
     * - Trim des détails
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        // Par défaut, on restock le produit
        if (!$this->has('restock')) {
            $data['restock'] = true;
        }

        if ($this->has('reason_details')) {
            $details = trim($this->input('reason_details'));
            $data['reason_details'] = $details !== '' ? $details : null;
        }

        $this->merge($data);
    }

    /**
     * Validation métier après les règles de base.
     *
     * @return array
     */
    public function after(): array
    {
        return [
            function ($validator) {
                // Vérifier qu'il y a au moins un item annulable
                $order = $this->route('order');
                $user = $this->user();

                if (!$order || !$user) return;

                $query = $order->items()
                    ->whereIn('seller_status', self::CANCELLABLE_STATUSES);

                if (!$user->isAdmin()) {
                    $query->where('seller_id', $user->id);
                }

                if ($query->count() === 0) {
                    $validator->errors()->add(
                        'reason',
                        'Aucun article annulable dans cette commande. Les articles déjà expédiés doivent passer par un retour.'
                    );
                }
            },
        ];
    }

    /**
     * Retourne les items ciblés par l'annulation.
     *
     * - Si `item_ids` fourni → uniquement ces items (vérifiés)
     * - Sinon → tous les items annulables du vendeur
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order\OrderItem>
     */
    public function getItemsToCancel()
    {
        $order = $this->route('order');
        $user = $this->user();

        $query = $order->items()
            ->whereIn('seller_status', self::CANCELLABLE_STATUSES);

        // Filtrer par vendeur (sauf admin)
        if ($user && !$user->isAdmin()) {
            $query->where('seller_id', $user->id);
        }

        // Si items spécifiques demandés
        if ($this->filled('item_ids')) {
            $query->whereIn('id', $this->input('item_ids'));
        }

        return $query->get();
    }

    /**
     * Le vendeur veut-il restocker le produit ?
     */
    public function shouldRestock(): bool
    {
        return (bool) $this->input('restock', true);
    }

    /**
     * Retourne le label lisible de la raison.
     */
    public function getReasonLabel(): string
    {
        return self::ALLOWED_REASONS[$this->input('reason')] ?? 'Annulation';
    }
}