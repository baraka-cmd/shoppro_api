<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Autoriser la requête
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation pour la mise à jour du profil
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $userId = Auth::id();

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $userId,
            'phone' => 'nullable|string|max:20',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'language' => 'nullable|string|max:10',
            'currency' => 'nullable|string|max:10',
        ];
    }

    /**
     * Messages d'erreur personnalisés
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => 'Le nom doit être une chaîne de caractères',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères',

            'email.email' => 'L\'adresse email est invalide',
            'email.unique' => 'Cette adresse email est déjà utilisée',
            'email.max' => 'L\'adresse email ne peut pas dépasser 255 caractères',

            'phone.string' => 'Le téléphone doit être une chaîne de caractères',
            'phone.max' => 'Le téléphone ne peut pas dépasser 20 caractères',

            'first_name.string' => 'Le prénom doit être une chaîne de caractères',
            'first_name.max' => 'Le prénom ne peut pas dépasser 100 caractères',

            'last_name.string' => 'Le nom de famille doit être une chaîne de caractères',
            'last_name.max' => 'Le nom de famille ne peut pas dépasser 100 caractères',

            'birth_date.date' => 'La date de naissance est invalide',
            'birth_date.before' => 'La date de naissance doit être dans le passé',

            'gender.in' => 'Le genre doit être : male, female ou other',

            'country.string' => 'Le pays doit être une chaîne de caractères',
            'country.max' => 'Le pays ne peut pas dépasser 100 caractères',

            'city.string' => 'La ville doit être une chaîne de caractères',
            'city.max' => 'La ville ne peut pas dépasser 100 caractères',

            'address.string' => 'L\'adresse doit être une chaîne de caractères',
            'address.max' => 'L\'adresse ne peut pas dépasser 500 caractères',

            'language.string' => 'La langue doit être une chaîne de caractères',
            'language.max' => 'La langue ne peut pas dépasser 10 caractères',

            'currency.string' => 'La devise doit être une chaîne de caractères',
            'currency.max' => 'La devise ne peut pas dépasser 10 caractères',
        ];
    }

    /**
     * Personnaliser la réponse en cas de validation échouée
     * 
     * @param Validator $validator
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Préparer les données pour la validation
     * 
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Nettoyer l'email si présent
        if ($this->has('email')) {
            $this->merge([
                'email' => trim(strtolower($this->email)),
            ]);
        }

        // Nettoyer les champs texte
        $textFields = [
            'name',
            'first_name',
            'last_name',
            'country',
            'city',
            'address',
            'language',
            'currency',
            'phone',
        ];

        foreach ($textFields as $field) {
            if ($this->has($field) && is_string($this->$field)) {
                $this->merge([
                    $field => trim($this->$field),
                ]);
            }
        }
    }

    /**
     * Récupérer les données validées pour la mise à jour du profil
     * 
     * @return array
     */
    public function validatedProfileData(): array
    {
        return array_filter([
            'name' => $this->name ?? null,
            'email' => $this->email ?? null,
            'phone' => $this->phone ?? null,
            'first_name' => $this->first_name ?? null,
            'last_name' => $this->last_name ?? null,
            'birth_date' => $this->birth_date ?? null,
            'gender' => $this->gender ?? null,
            'country' => $this->country ?? null,
            'city' => $this->city ?? null,
            'address' => $this->address ?? null,
            'language' => $this->language ?? null,
            'currency' => $this->currency ?? null,
        ], function ($value) {
            return $value !== null;
        });
    }
}