<?php

namespace App\Http\Requests\Api\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Request : UpdateSellerProfileRequest
 *
 * Valide la mise à jour du profil boutique vendeur.
 *
 * Champs modifiables :
 *   - shop_name       : nom public de la boutique
 *   - description     : description publique
 *   - phone           : téléphone professionnel
 *   - email           : email professionnel (pas l'email du compte)
 *   - address         : adresse physique
 *   - city            : ville
 *   - country         : pays
 *
 * Champs NON modifiables via ce request :
 *   - shop_slug       : généré automatiquement, unique
 *   - kyc_status      : géré par submitKyc()
 *   - commission_rate : géré par admin
 *   - balance         : géré par le système
 *
 * @package App\Http\Requests\Api\Seller
 */
class UpdateSellerProfileRequest extends FormRequest
{
    /**
     * Autorise la requête.
     *
     * L'utilisateur doit être authentifié ET vendeur (ou admin).
     * La vérification fine de propriété est faite dans le contrôleur.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isSeller() || $user->isAdmin());
    }

    /**
     * Règles de validation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shop_name' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:150',
                // Unicité du nom de boutique (hors profil actuel)
                Rule::unique('seller_profiles', 'shop_name')
                    ->ignore($this->user()->sellerProfile?->id),
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000',
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+]?[0-9\s\-\(\)]{6,20}$/',
            ],

            'email' => [
                'sometimes',
                'nullable',
                'email:rfc,dns',
                'max:150',
            ],

            'address' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'city' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'country' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
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
            // shop_name
            'shop_name.required' => 'Le nom de la boutique est obligatoire.',
            'shop_name.string'   => 'Le nom de la boutique doit être un texte.',
            'shop_name.min'      => 'Le nom de la boutique doit contenir au moins :min caractères.',
            'shop_name.max'      => 'Le nom de la boutique ne peut pas dépasser :max caractères.',
            'shop_name.unique'   => 'Ce nom de boutique est déjà utilisé.',

            // description
            'description.string' => 'La description doit être un texte.',
            'description.max'    => 'La description ne peut pas dépasser :max caractères.',

            // phone
            'phone.string' => 'Le téléphone doit être un texte.',
            'phone.max'    => 'Le téléphone ne peut pas dépasser :max caractères.',
            'phone.regex'  => 'Le format du téléphone est invalide.',

            // email
            'email.email' => 'L\'adresse email professionnelle est invalide.',
            'email.max'   => 'L\'email ne peut pas dépasser :max caractères.',

            // address
            'address.string' => 'L\'adresse doit être un texte.',
            'address.max'    => 'L\'adresse ne peut pas dépasser :max caractères.',

            // city
            'city.string' => 'La ville doit être un texte.',
            'city.max'    => 'La ville ne peut pas dépasser :max caractères.',

            // country
            'country.string' => 'Le pays doit être un texte.',
            'country.max'    => 'Le pays ne peut pas dépasser :max caractères.',
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
            'shop_name'   => 'nom de la boutique',
            'description' => 'description',
            'phone'       => 'téléphone',
            'email'       => 'email professionnel',
            'address'     => 'adresse',
            'city'        => 'ville',
            'country'     => 'pays',
        ];
    }

    /**
     * Prépare les données avant validation.
     *
     * - Trim des champs texte
     * - Génération d'un slug UNIQUEMENT si le nom change
     *   (géré dans le contrôleur, pas ici, pour éviter les collisions)
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('shop_name')) {
            $data['shop_name'] = trim($this->input('shop_name'));
        }

        if ($this->has('description')) {
            $data['description'] = $this->input('description')
                ? trim($this->input('description'))
                : null;
        }

        if ($this->has('phone')) {
            $data['phone'] = $this->input('phone')
                ? trim($this->input('phone'))
                : null;
        }

        if ($this->has('email')) {
            $data['email'] = $this->input('email')
                ? strtolower(trim($this->input('email')))
                : null;
        }

        if ($this->has('address')) {
            $data['address'] = $this->input('address')
                ? trim($this->input('address'))
                : null;
        }

        if ($this->has('city')) {
            $data['city'] = $this->input('city')
                ? trim($this->input('city'))
                : null;
        }

        if ($this->has('country')) {
            $data['country'] = $this->input('country')
                ? trim($this->input('country'))
                : null;
        }

        $this->merge($data);
    }
}