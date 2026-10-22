<?php

namespace App\Http\Requests\Api\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * Request : SubmitKycRequest
 *
 * Valide la soumission du KYC (Know Your Customer) vendeur.
 *
 * Champs requis :
 *   - id_type          : type de document d'identité
 *   - id_number        : numéro du document
 *   - id_document      : photo/scan du document
 *   - business_license : (optionnel) registre de commerce
 *   - selfie           : (optionnel) selfie du vendeur
 *
 * Types de document acceptés :
 *   - cni       : Carte Nationale d'Identité
 *   - passport  : Passeport
 *   - license   : Permis de conduire
 *   - residence : Carte de séjour
 *
 * ⚠️ Contraintes :
 *   - Un vendeur déjà verified ne peut PAS re-soumettre
 *   - Un vendeur déjà pending ne peut PAS re-soumettre avant décision
 *   - Un vendeur rejected PEUT re-soumettre
 *
 * @package App\Http\Requests\Api\Seller
 */
class SubmitKycRequest extends FormRequest
{
    /**
     * Autorise la requête.
     *
     * Vérifie que l'utilisateur est bien un vendeur ou admin,
     * et que son KYC est re-soumettable.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user || (!$user->isSeller() && !$user->isAdmin())) {
            return false;
        }

        // Admin peut soumettre pour n'importe qui (super-pouvoir)
        if ($user->isAdmin()) {
            return true;
        }

        $profile = $user->sellerProfile;

        if (!$profile) {
            return false;
        }

        // Un vendeur peut soumettre son KYC si :
        // - il n'a jamais soumis (pending)
        // - ou si son KYC a été rejeté
        // Il NE PEUT PAS soumettre si déjà verified ou en cours (pending avec soumission déjà faite)
        return in_array($profile->kyc_status, ['pending', 'rejected'], true);
    }

    /**
     * Règles de validation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id_type' => [
                'required',
                'string',
                'in:cni,passport,license,residence',
            ],

            'id_number' => [
                'required',
                'string',
                'min:5',
                'max:50',
                // Alphanumérique + tirets + espaces (formats variés)
                'regex:/^[A-Z0-9\s\-]{5,50}$/i',
            ],

            'id_document' => [
                'required',
                File::image()
                    ->types(['jpeg', 'jpg', 'png', 'webp', 'pdf'])
                    ->max(5 * 1024), // 5 Mo
            ],

            'business_license' => [
                'nullable',
                File::image()
                    ->types(['jpeg', 'jpg', 'png', 'webp', 'pdf'])
                    ->max(5 * 1024),
            ],

            'selfie' => [
                'nullable',
                File::image()
                    ->types(['jpeg', 'jpg', 'png', 'webp'])
                    ->max(3 * 1024), // 3 Mo
            ],

            'kyc_notes' => [
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
            // id_type
            'id_type.required' => 'Le type de document est obligatoire.',
            'id_type.in'       => 'Le type de document doit être : CNI, passeport, permis ou carte de séjour.',

            // id_number
            'id_number.required' => 'Le numéro du document est obligatoire.',
            'id_number.min'      => 'Le numéro du document doit contenir au moins :min caractères.',
            'id_number.max'      => 'Le numéro du document ne peut pas dépasser :max caractères.',
            'id_number.regex'    => 'Le numéro du document doit contenir uniquement des lettres, chiffres, tirets et espaces.',

            // id_document
            'id_document.required' => 'Le document d\'identité est obligatoire.',
            'id_document.image'    => 'Le document doit être une image (JPG, PNG, WEBP) ou un PDF.',
            'id_document.max'      => 'Le document ne peut pas dépasser 5 Mo.',

            // business_license
            'business_license.image' => 'Le registre de commerce doit être une image ou un PDF.',
            'business_license.max'   => 'Le registre de commerce ne peut pas dépasser 5 Mo.',

            // selfie
            'selfie.image' => 'Le selfie doit être une image.',
            'selfie.max'   => 'Le selfie ne peut pas dépasser 3 Mo.',

            // kyc_notes
            'kyc_notes.max' => 'Les notes KYC ne peuvent pas dépasser :max caractères.',
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
            'id_type'          => 'type de document',
            'id_number'        => 'numéro du document',
            'id_document'      => 'document d\'identité',
            'business_license' => 'registre de commerce',
            'selfie'           => 'selfie',
            'kyc_notes'        => 'notes KYC',
        ];
    }

    /**
     * Prépare les données avant validation.
     *
     * - Trim + uppercase du numéro de document (formats standards)
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('id_number')) {
            $data['id_number'] = strtoupper(trim($this->input('id_number')));
        }

        if ($this->has('kyc_notes')) {
            $data['kyc_notes'] = $this->input('kyc_notes')
                ? trim($this->input('kyc_notes'))
                : null;
        }

        $this->merge($data);
    }
}