<?php

namespace App\Http\Requests\Api\Support;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTicketRequest extends FormRequest
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

            'category' => [
                'required',
                'string',
                'in:order,payment,delivery,product,account,technical,other',
            ],

            'description' => 'required|string|min:10|max:2000',

            'order_id' => 'nullable|integer|exists:orders,id',

            'priority' => [
                'nullable',
                'string',
                'in:low,medium,high,urgent',
            ],
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
            'category.string' => 'La catégorie doit être une chaîne de caractères',
            'category.in' => 'La catégorie sélectionnée est invalide',

            'description.required' => 'La description est obligatoire',
            'description.string' => 'La description doit être une chaîne de caractères',
            'description.min' => 'La description doit contenir au moins 10 caractères',
            'description.max' => 'La description ne peut pas dépasser 2000 caractères',

            'order_id.integer' => 'L\'identifiant de la commande est invalide',
            'order_id.exists' => 'La commande sélectionnée n\'existe pas',

            'priority.string' => 'La priorité doit être une chaîne de caractères',
            'priority.in' => 'La priorité sélectionnée est invalide',
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
        if ($this->has('subject') && is_string($this->subject)) {
            $this->merge([
                'subject' => trim($this->subject),
            ]);
        }

        if ($this->has('description') && is_string($this->description)) {
            $this->merge([
                'description' => trim($this->description),
            ]);
        }
    }
}