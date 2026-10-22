<?php

namespace App\Http\Requests\Api\Seller;

use App\Models\Catalog\Category;
use App\Models\Catalog\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request : StoreSellerProductRequest
 *
 * Valide la création d'un nouveau produit par un vendeur.
 *
 * Règles métier critiques :
 *   - Le vendeur doit être KYC verified (vérifié dans authorize())
 *   - Le SKU doit être unique globalement (pas seulement pour ce vendeur)
 *   - La catégorie doit exister et être active
 *   - La marque (optionnelle) doit exister et être active
 *   - Le prix doit être > 0
 *   - Le stock initial peut être 0 (le vendeur pourra l'ajuster plus tard)
 *   - Le slug est généré automatiquement (pas dans les règles)
 *
 * Sécurité :
 *   - Aucun champ "seller_id" n'est accepté (forcé côté contrôleur)
 *   - Aucun champ "is_active" n'est accepté (draft par défaut)
 *   - Aucun champ "views_count", "sales_count" n'est accepté
 *   - Les champs SEO sont optionnels mais validés
 *
 * @package App\Http\Requests\Api\Seller
 */
class StoreSellerProductRequest extends FormRequest
{
    /**
     * Autorise la requête.
     *
     * Vérifie :
     *   - L'utilisateur est authentifié et vendeur
     *   - Son profil vendeur existe
     *   - Son KYC est validé (verified)
     *   - Son compte vendeur est actif
     */
    public function authorize(): bool
    {
        $user = $this->user();

        // Doit être vendeur ou admin
        if (!$user || (!$user->isSeller() && !$user->isAdmin())) {
            return false;
        }

        // Admin : autorisé (peut créer pour n'importe quel vendeur via seller_id plus tard)
        if ($user->isAdmin()) {
            return true;
        }

        // Vendeur : profil + KYC obligatoires
        $profile = $user->sellerProfile;

        if (!$profile) {
            return false;
        }

        if (!$profile->is_active) {
            return false;
        }

        if (!$profile->isKycVerified()) {
            return false;
        }

        return true;
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
            // INFORMATIONS DE BASE
            // ============================================
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],

            'short_description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'description' => [
                'nullable',
                'string',
                'max:10000',
            ],

            // ============================================
            // PRIX & TAXES
            // ============================================
            'price' => [
                'required',
                'numeric',
                'min:1',
                'max:99999999',
            ],

            'discount_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999',
                // Doit être inférieur au prix normal
                'lt:price',
            ],

            'cost_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999',
            ],

            'tax_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            // ============================================
            // STOCK & SKU
            // ============================================
            'stock' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],

            'min_stock' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999',
            ],

            'max_stock' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999',
                'gte:min_stock',
            ],

            'sku' => [
                'required',
                'string',
                'min:3',
                'max:100',
                // Unicité globale (tous vendeurs confondus)
                Rule::unique('products', 'sku'),
                // Format : alphanumérique + tirets + underscores
                'regex:/^[A-Z0-9\-_]+$/i',
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:100',
                // Unicité globale si fourni
                Rule::unique('products', 'barcode')->whereNotNull('barcode'),
            ],

            // ============================================
            // RELATIONS
            // ============================================
            'category_id' => [
                'required',
                'integer',
                // Doit exister et être actif
                Rule::exists('categories', 'id')->where('is_active', true),
            ],

            'subcategory_id' => [
                'nullable',
                'integer',
                Rule::exists('subcategories', 'id'),
            ],

            'brand_id' => [
                'nullable',
                'integer',
                Rule::exists('brands', 'id')->where('is_active', true),
            ],

            // ============================================
            // DISPONIBILITÉ
            // ============================================
            'available_from' => [
                'nullable',
                'date',
                'date_format:Y-m-d',
            ],

            'available_until' => [
                'nullable',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:available_from',
            ],

            // ============================================
            // SEO (optionnel)
            // ============================================
            'meta_title' => [
                'nullable',
                'string',
                'max:200',
            ],

            'meta_description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'meta_keywords' => [
                'nullable',
                'string',
                'max:255',
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
            // name
            'name.required' => 'Le nom du produit est obligatoire.',
            'name.string'   => 'Le nom du produit doit être un texte.',
            'name.min'      => 'Le nom du produit doit contenir au moins :min caractères.',
            'name.max'      => 'Le nom du produit ne peut pas dépasser :max caractères.',

            // short_description
            'short_description.max' => 'La description courte ne peut pas dépasser :max caractères.',

            // description
            'description.max' => 'La description ne peut pas dépasser :max caractères.',

            // price
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric'  => 'Le prix doit être un nombre.',
            'price.min'      => 'Le prix doit être supérieur à 0.',
            'price.max'      => 'Le prix ne peut pas dépasser :max.',

            // discount_price
            'discount_price.numeric' => 'Le prix réduit doit être un nombre.',
            'discount_price.min'     => 'Le prix réduit ne peut pas être négatif.',
            'discount_price.lt'      => 'Le prix réduit doit être inférieur au prix normal.',

            // cost_price
            'cost_price.numeric' => 'Le prix d\'achat doit être un nombre.',
            'cost_price.min'     => 'Le prix d\'achat ne peut pas être négatif.',

            // tax_rate
            'tax_rate.numeric' => 'Le taux de taxe doit être un nombre.',
            'tax_rate.min'     => 'Le taux de taxe ne peut pas être négatif.',
            'tax_rate.max'     => 'Le taux de taxe ne peut pas dépasser 100%.',

            // stock
            'stock.required' => 'Le stock initial est obligatoire.',
            'stock.integer'  => 'Le stock doit être un nombre entier.',
            'stock.min'      => 'Le stock ne peut pas être négatif.',
            'stock.max'      => 'Le stock ne peut pas dépasser :max.',

            // min_stock
            'min_stock.integer' => 'Le stock minimum doit être un nombre entier.',
            'min_stock.min'     => 'Le stock minimum ne peut pas être négatif.',

            // max_stock
            'max_stock.integer' => 'Le stock maximum doit être un nombre entier.',
            'max_stock.gte'     => 'Le stock maximum doit être supérieur ou égal au stock minimum.',

            // sku
            'sku.required' => 'Le SKU est obligatoire.',
            'sku.string'   => 'Le SKU doit être un texte.',
            'sku.min'      => 'Le SKU doit contenir au moins :min caractères.',
            'sku.max'      => 'Le SKU ne peut pas dépasser :max caractères.',
            'sku.unique'   => 'Ce SKU est déjà utilisé par un autre produit.',
            'sku.regex'    => 'Le SKU doit contenir uniquement des lettres, chiffres, tirets et underscores.',

            // barcode
            'barcode.max'    => 'Le code-barres ne peut pas dépasser :max caractères.',
            'barcode.unique' => 'Ce code-barres est déjà utilisé par un autre produit.',

            // category_id
            'category_id.required' => 'La catégorie est obligatoire.',
            'category_id.integer'  => 'La catégorie est invalide.',
            'category_id.exists'   => 'La catégorie sélectionnée n\'existe pas ou est inactive.',

            // subcategory_id
            'subcategory_id.exists' => 'La sous-catégorie sélectionnée n\'existe pas.',

            // brand_id
            'brand_id.exists' => 'La marque sélectionnée n\'existe pas ou est inactive.',

            // available_from
            'available_from.date'        => 'La date de disponibilité est invalide.',
            'available_from.date_format' => 'La date de disponibilité doit être au format AAAA-MM-JJ.',

            // available_until
            'available_until.date'            => 'La date de fin de disponibilité est invalide.',
            'available_until.date_format'     => 'La date de fin doit être au format AAAA-MM-JJ.',
            'available_until.after_or_equal'  => 'La date de fin doit être postérieure ou égale à la date de début.',

            // SEO
            'meta_title.max'       => 'Le titre SEO ne peut pas dépasser :max caractères.',
            'meta_description.max' => 'La description SEO ne peut pas dépasser :max caractères.',
            'meta_keywords.max'    => 'Les mots-clés SEO ne peuvent pas dépasser :max caractères.',
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
            'name'              => 'nom',
            'short_description' => 'description courte',
            'description'       => 'description',
            'price'             => 'prix',
            'discount_price'    => 'prix réduit',
            'cost_price'        => 'prix d\'achat',
            'tax_rate'          => 'taux de taxe',
            'stock'             => 'stock',
            'min_stock'         => 'stock minimum',
            'max_stock'         => 'stock maximum',
            'sku'               => 'SKU',
            'barcode'           => 'code-barres',
            'category_id'       => 'catégorie',
            'subcategory_id'    => 'sous-catégorie',
            'brand_id'          => 'marque',
            'available_from'    => 'date de disponibilité',
            'available_until'   => 'date de fin de disponibilité',
            'meta_title'        => 'titre SEO',
            'meta_description'  => 'description SEO',
            'meta_keywords'     => 'mots-clés SEO',
        ];
    }

    /**
     * Prépare les données avant validation.
     *
     * - Trim des champs texte
     * - Uppercase du SKU (standard industrie)
     * - Nettoyage des balises HTML dans les descriptions (sécurité XSS)
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('name')) {
            $data['name'] = trim($this->input('name'));
        }

        if ($this->has('sku')) {
            $data['sku'] = strtoupper(trim($this->input('sku')));
        }

        if ($this->has('barcode')) {
            $data['barcode'] = $this->input('barcode')
                ? trim($this->input('barcode'))
                : null;
        }

        if ($this->has('short_description')) {
            $data['short_description'] = $this->input('short_description')
                ? strip_tags(trim($this->input('short_description')))
                : null;
        }

        if ($this->has('description')) {
            $data['description'] = $this->input('description')
                ? strip_tags(trim($this->input('description')))
                : null;
        }

        if ($this->has('meta_title')) {
            $data['meta_title'] = $this->input('meta_title')
                ? trim($this->input('meta_title'))
                : null;
        }

        if ($this->has('meta_description')) {
            $data['meta_description'] = $this->input('meta_description')
                ? trim($this->input('meta_description'))
                : null;
        }

        $this->merge($data);
    }

    /**
     * Validation supplémentaire après les règles de base.
     *
     * Vérifie la cohérence métier :
     *   - Si discount_price est fourni, is_on_sale doit être true
     *   - Si available_from > aujourd'hui, le produit ne sera pas immédiatement disponible
     */
    public function after(): array
    {
        return [
            function ($validator) {
                // Vérifier que le discount_price n'est pas fourni sans is_on_sale
                if ($this->filled('discount_price') && $this->input('discount_price') > 0) {
                    // On force is_on_sale = true côté contrôleur, mais on avertit si incohérent
                    if (!$this->has('is_on_sale')) {
                        $this->merge(['is_on_sale' => true]);
                    }
                }

                // Vérifier la cohérence des dates de disponibilité
                if ($this->filled('available_from') && $this->filled('available_until')) {
                    $from = \Carbon\Carbon::parse($this->input('available_from'));
                    $until = \Carbon\Carbon::parse($this->input('available_until'));

                    if ($until->lt($from)) {
                        $validator->errors()->add(
                            'available_until',
                            'La date de fin doit être postérieure à la date de début.'
                        );
                    }
                }
            },
        ];
    }
}