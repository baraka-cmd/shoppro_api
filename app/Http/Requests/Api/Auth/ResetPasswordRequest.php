<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResetPasswordRequest extends FormRequest
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
     * Règles de validation pour la réinitialisation du mot de passe
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|string|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
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
            'email.required' => 'L\'adresse email est obligatoire',
            'email.email' => 'L\'adresse email est invalide',
            'email.exists' => 'Aucun compte n\'est associé à cette adresse email',
            
            'token.required' => 'Le jeton de réinitialisation est obligatoire',
            'token.string' => 'Le jeton doit être une chaîne de caractères',
            
            'password.required' => 'Le nouveau mot de passe est obligatoire',
            'password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères',
            'password.confirmed' => 'La confirmation du nouveau mot de passe ne correspond pas',
            
            'password_confirmation.required' => 'La confirmation du nouveau mot de passe est obligatoire',
            'password_confirmation.min' => 'La confirmation doit contenir au moins 8 caractères',
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
        // Nettoyer l'email
        if ($this->has('email')) {
            $this->merge([
                'email' => trim(strtolower($this->email)),
            ]);
        }
    }
}