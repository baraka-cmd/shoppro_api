<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
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
     * Règles de validation pour l'inscription
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
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
            'name.required' => 'Le nom est obligatoire',
            'name.string' => 'Le nom doit être une chaîne de caractères',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères',
            
            'email.required' => 'L\'adresse email est obligatoire',
            'email.email' => 'L\'adresse email est invalide',
            'email.unique' => 'Cette adresse email est déjà utilisée',
            'email.max' => 'L\'adresse email ne peut pas dépasser 255 caractères',
            
            'password.required' => 'Le mot de passe est obligatoire',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas',
            
            'password_confirmation.required' => 'La confirmation du mot de passe est obligatoire',
            'password_confirmation.min' => 'La confirmation doit contenir au moins 8 caractères',
            
            'phone.max' => 'Le téléphone ne peut pas dépasser 20 caractères',
            
            'first_name.string' => 'Le prénom doit être une chaîne de caractères',
            'first_name.max' => 'Le prénom ne peut pas dépasser 100 caractères',
            
            'last_name.string' => 'Le nom de famille doit être une chaîne de caractères',
            'last_name.max' => 'Le nom de famille ne peut pas dépasser 100 caractères',
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
        // Nettoyer l'email (supprimer les espaces)
        if ($this->has('email')) {
            $this->merge([
                'email' => trim(strtolower($this->email)),
            ]);
        }

        // Nettoyer le nom (supprimer les espaces en début/fin)
        if ($this->has('name')) {
            $this->merge([
                'name' => trim($this->name),
            ]);
        }
    }
}