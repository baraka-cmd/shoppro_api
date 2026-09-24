<?php

namespace App\Http\Requests\Api\Support;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ContactRequest extends FormRequest
{
    /**
     * Autoriser la requête.
     *
     * @return bool
     */
    public function authorize(): bool
    {
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
            'subject' => 'required|string|max:255',
            'category' => 'required|string|in:general,order,payment,delivery,return,account,technical,other',
            'message' => 'required|string|min:10|max:2000',
            'order_id' => 'nullable|integer|exists:orders,id',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'attachment' => 'nullable|string|max:255',
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
            'subject.required' => 'Le sujet est obligatoire',
            'subject.string' => 'Le sujet doit être une chaîne de caractères',
            'subject.max' => 'Le sujet ne peut pas dépasser 255 caractères',

            'category.required' => 'La catégorie est obligatoire',
            'category.in' => 'La catégorie sélectionnée est invalide',

            'message.required' => 'Le message est obligatoire',
            'message.min' => 'Le message doit contenir au moins 10 caractères',
            'message.max' => 'Le message ne peut pas dépasser 2000 caractères',

            'order_id.exists' => 'La commande sélectionnée n\'existe pas',

            'priority.in' => 'La priorité sélectionnée est invalide',

            'attachment.max' => 'Le lien de la pièce jointe est trop long',
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
        // Nettoyer les champs texte
        $textFields = ['subject', 'category', 'message', 'priority'];

        foreach ($textFields as $field) {
            if ($this->has($field) && is_string($this->$field)) {
                $this->merge([
                    $field => trim($this->$field),
                ]);
            }
        }
    }
}