<?php

namespace App\Http\Requests\Api\Support;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class TicketMessageRequest extends FormRequest
{
    /**
     * Autoriser la requête.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Règles de validation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => 'required|string|min:5|max:2000',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Le message est obligatoire',
            'message.min' => 'Le message doit contenir au moins 5 caractères',
            'message.max' => 'Le message ne peut pas dépasser 2000 caractères',

            'attachment.image' => 'Le fichier doit être une image',
            'attachment.mimes' => 'Le format doit être : jpeg, png, jpg ou webp',
            'attachment.max' => 'L\'image ne peut pas dépasser 2 Mo',
        ];
    }

    /**
     * Personnaliser la réponse en cas de validation échouée.
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
     * Préparer les données pour la validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('message') && is_string($this->message)) {
            $this->merge([
                'message' => trim($this->message),
            ]);
        }
    }
}