<?php

namespace App\Http\Requests\Api\Address;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAddressRequest extends FormRequest
{
    /**
     * Autoriser la requête
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation pour modifier une adresse
     * Utilise `sometimes` pour permettre les mises à jour partielles
     */
    public function rules(): array
    {
        return [
            // Informations du destinataire
            'full_name' => 'sometimes|required|string|max:150',
            'phone' => 'sometimes|required|string|max:20',
            'email' => 'nullable|email|max:150',

            // Adresse détaillée
            'address_line1' => 'sometimes|required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'sometimes|required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',

            // Type d'adresse
            'type' => 'nullable|in:home,work,other',

            // Adresse par défaut
            'is_default' => 'nullable|boolean',

            // Géolocalisation
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',

            // Instructions
            'delivery_instructions' => 'nullable|string|max:500',
        ];
    }

    /**
     * Messages d'erreur en français
     */
    public function messages(): array
    {
        return [
            // Nom complet
            'full_name.required' => 'Le nom complet est obligatoire.',
            'full_name.string' => 'Le nom complet doit être une chaîne de caractères.',
            'full_name.max' => 'Le nom complet ne peut pas dépasser 150 caractères.',

            // Téléphone
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'phone.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'phone.max' => 'Le numéro de téléphone ne peut pas dépasser 20 caractères.',

            // Email
            'email.email' => 'L\'adresse email doit être valide.',
            'email.max' => 'L\'adresse email ne peut pas dépasser 150 caractères.',

            // Adresse ligne 1
            'address_line1.required' => 'L\'adresse est obligatoire.',
            'address_line1.string' => 'L\'adresse doit être une chaîne de caractères.',
            'address_line1.max' => 'L\'adresse ne peut pas dépasser 255 caractères.',

            // Adresse ligne 2
            'address_line2.string' => 'Le complément d\'adresse doit être une chaîne de caractères.',
            'address_line2.max' => 'Le complément d\'adresse ne peut pas dépasser 255 caractères.',

            // Ville
            'city.required' => 'La ville est obligatoire.',
            'city.string' => 'La ville doit être une chaîne de caractères.',
            'city.max' => 'La ville ne peut pas dépasser 100 caractères.',

            // Région / État
            'state.string' => 'La région doit être une chaîne de caractères.',
            'state.max' => 'La région ne peut pas dépasser 100 caractères.',

            // Code postal
            'postal_code.string' => 'Le code postal doit être une chaîne de caractères.',
            'postal_code.max' => 'Le code postal ne peut pas dépasser 20 caractères.',

            // Pays
            'country.string' => 'Le pays doit être une chaîne de caractères.',
            'country.max' => 'Le pays ne peut pas dépasser 100 caractères.',

            // Type
            'type.in' => 'Le type d\'adresse doit être : home, work ou other.',

            // Défaut
            'is_default.boolean' => 'Le champ "adresse par défaut" doit être vrai ou faux.',

            // Géolocalisation
            'latitude.numeric' => 'La latitude doit être un nombre.',
            'latitude.between' => 'La latitude doit être comprise entre -90 et 90.',
            'longitude.numeric' => 'La longitude doit être un nombre.',
            'longitude.between' => 'La longitude doit être comprise entre -180 et 180.',

            // Instructions
            'delivery_instructions.string' => 'Les instructions de livraison doivent être une chaîne de caractères.',
            'delivery_instructions.max' => 'Les instructions de livraison ne peuvent pas dépasser 500 caractères.',
        ];
    }

    /**
     * Noms de champs personnalisés
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'nom complet',
            'phone' => 'téléphone',
            'email' => 'email',
            'address_line1' => 'adresse',
            'address_line2' => 'complément d\'adresse',
            'city' => 'ville',
            'state' => 'région',
            'postal_code' => 'code postal',
            'country' => 'pays',
            'type' => 'type d\'adresse',
            'is_default' => 'adresse par défaut',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'delivery_instructions' => 'instructions de livraison',
        ];
    }

    /**
     * Préparer les données avant validation
     */
    protected function prepareForValidation(): void
    {
        // Convertir is_default en booléen si présent
        if ($this->has('is_default')) {
            $this->merge([
                'is_default' => $this->boolean('is_default'),
            ]);
        }
    }

    /**
     * Gérer l'échec de validation (réponse JSON)
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}