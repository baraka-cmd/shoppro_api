<?php

namespace App\Http\Requests\Api\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request : UpdateSellerProductRequest
 *
 * Valide la mise à jour d'un produit existant par un vendeur.
 *
 * Différences avec StoreSellerProductRequest :
 *   - Le champ "price" est optionnel (sometimes)
 *   - Le SKU ignore le produit actuel (Rule::unique->ignore)
 *   - Aucune modification du statut (is_active) via ce endpoint
 *   - Le produit doit appartenir au vendeur (vérifié dans authorize)
 *
 * @package App\Http\Requests\Api\Seller
 */
class UpdateSellerProductRequest extends FormRequest
{
    /**
     * Autorise la requête.
     *
     * Vérifie :
     *   - L'utilisateur est authentifié et vendeur
     *   - Son KYC est validé
     *   - Le produit appartient au vendeur (via ProductPolicy)
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        if (!$user->isSeller() && !$user->isAdmin()) {
            return false;
        }

        $product = $this->route('product');

        if (!$product) {
            return false;
        }

        // Utiliser la Policy ProductPolicy::update
        if ($user->can('update', $product)) {
            return true;
        }

        return false;
    }

    /**
     * Règles de validation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $productId = $product ? $product->id : 0;

        return [
            // ============================================
            // INFORMATIONS DE BASE
            // ============================================
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:255',
            ],

            'short_description' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:10000',
            ],

            // ============================================
            // PRIX & TAXES
            // ============================================
            'price' => [
                'sometimes',
                'required',
                'numeric',
                'min:1',
                'max:99999999',
            ],

            'discount_price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:99999999',
                'lt:price',
            ],

            'cost_price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:99999999',
            ],

            'tax_rate' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            // ============================================
            // STOCK & SKU
            // ============================================
            'stock' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],

            'min_stock' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:999999',
            ],

            'max_stock' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:999999',
                'gte:min_stock',
            ],

            'sku' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:100',
                // Ignore le produit actuel
                Rule::unique('products', 'sku')->ignore($productId),
                'regex:/^[A-Z0-9\-_]+$/i',
            ],

            'barcode' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')
                    ->ignore($productId)
                    ->whereNotNull('barcode'),
            ],

            // ============================================
            // RELATIONS
            // ============================================
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],

            'subcategory_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('subcategories', 'id'),
            ],

            'brand_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('brands', 'id')->where('is_active', true),
            ],

            // ============================================
            // DISPONIBILITÉ
            // ============================================
            'available_from' => [
                'sometimes',
                'nullable',
                'date',
                'date_format:Y-m-d',
            ],

            'available_until' => [
                'sometimes',
                'nullable',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:available_from',
            ],

            // ============================================
            // SEO
            // ============================================
            'meta_title' => [
                'sometimes',
                'nullable',
                'string',
                'max:200',
            ],

            'meta_description' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],

            'meta_keywords' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Messages personnalisés.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du produit est obligatoire.',
            'name.string'   => 'Le nom du produit doit être un texte.',
            'name.min'      => 'Le nom du produit doit contenir au moins :min caractères.',
            'name.max'      => 'Le nom du produit ne peut pas dépasser :max caractères.',

            'short_description.max' => 'La description courte ne peut pas dépasser :max caractères.',
            'description.max'       => 'La description ne peut pas dépasser :max caractères.',

            'price.required' => 'Le prix est obligatoire.',
            'price.numeric'  => 'Le prix doit être un nombre.',
            'price.min'      => 'Le prix doit être supérieur à 0.',
            'price.max'      => 'Le prix ne peut pas dépasser :max.',

            'discount_price.numeric' => 'Le prix réduit doit être un nombre.',
            'discount_price.min'     => 'Le prix réduit ne peut pas être négatif.',
            'discount_price.lt'      => 'Le prix réduit doit être inférieur au prix normal.',

            'cost_price.numeric' => 'Le prix d\'achat doit être un nombre.',
            'cost_price.min'     => 'Le prix d\'achat ne peut pas être négatif.',

            'tax_rate.numeric' => 'Le taux de taxe doit être un nombre.',
            'tax_rate.min'     => 'Le taux de taxe ne peut pas être négatif.',
            'tax_rate.max'     => 'Le taux de taxe ne peut pas dépasser 100%.',

            'stock.required' => 'Le stock est obligatoire.',
            'stock.integer'  => 'Le stock doit être un nombre entier.',
            'stock.min'      => 'Le stock ne peut pas être négatif.',
            'stock.max'      => 'Le stock ne peut pas dépasser :max.',

            'min_stock.integer' => 'Le stock minimum doit être un nombre entier.',
            'min_stock.min'     => 'Le stock minimum ne peut pas être négatif.',

            'max_stock.integer' => 'Le stock maximum doit être un nombre entier.',
            'max_stock.gte'     => 'Le stock maximum doit être supérieur ou égal au stock minimum.',

            'sku.required' => 'Le SKU est obligatoire.',
            'sku.string'   => 'Le SKU doit être un texte.',
            'sku.min'      => 'Le SKU doit contenir au moins :min caractères.',
            'sku.max'      => 'Le SKU ne peut pas dépasser :max caractères.',
            'sku.unique'   => 'Ce SKU est déjà utilisé par un autre produit.',
            'sku.regex'    => 'Le SKU doit contenir uniquement des lettres, chiffres, tirets et underscores.',

            'barcode.max'    => 'Le code-barres ne peut pas dépasser :max caractères.',
            'barcode.unique' => 'Ce code-barres est déjà utilisé par un autre produit.',

            'category_id.required' => 'La catégorie est obligatoire.',
            'category_id.integer'  => 'La catégorie est invalide.',
            'category_id.exists'   => 'La catégorie sélectionnée n\'existe pas ou est inactive.',

            'subcategory_id.exists' => 'La sous-catégorie sélectionnée n\'existe pas.',
            'brand_id.exists'       => 'La marque sélectionnée n\'existe pas ou est inactive.',

            'available_from.date'        => 'La date de disponibilité est invalide.',
            'available_from.date_format' => 'La date de disponibilité doit être au format AAAA-MM-JJ.',
            'available_until.date'       => 'La date de fin de disponibilité est invalide.',
            'available_until.date_format' => 'La date de fin doit être au format AAAA-MM-JJ.',
            'available_until.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',

            'meta_title.max'       => 'Le titre SEO ne peut pas dépasser :max caractères.',
            'meta_description.max' => 'La description SEO ne peut pas dépasser :max caractères.',
            'meta_keywords.max'    => 'Les mots-clés SEO ne peuvent pas dépasser :max caractères.',
        ];
    }

    /**
     * Noms des attributs.
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
     * Validation métier conditionnelle.
     */
    public function after(): array
    {
        return [
            function ($validator) {
                $product = $this->route('product');

                // Vérifier la cohérence des prix
                if ($this->filled('discount_price') && $product) {
                    $price = $this->input('price', $product->price);
                    $discount = $this->input('discount_price');

                    if ($discount >= $price) {
                        $validator->errors()->add(
                            'discount_price',
                            'Le prix réduit doit être inférieur au prix normal.'
                        );
                    }
                }

                // Vérifier la cohérence des dates
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