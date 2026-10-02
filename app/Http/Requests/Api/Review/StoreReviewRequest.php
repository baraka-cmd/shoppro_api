<?php

namespace App\Http\Requests\Api\Review;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:5|max:500',
            'title' => 'nullable|string|max:100',
            'quality_rating' => 'nullable|integer|min:1|max:5',
            'price_rating' => 'nullable|integer|min:1|max:5',
            'shipping_rating' => 'nullable|integer|min:1|max:5',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'L\'identifiant du produit est obligatoire.',
            'product_id.exists' => 'Le produit n\'existe pas.',
            'rating.required' => 'La note est obligatoire.',
            'rating.min' => 'La note doit être au minimum de 1 étoile.',
            'rating.max' => 'La note ne peut pas dépasser 5 étoiles.',
            'comment.required' => 'Le commentaire est obligatoire.',
            'comment.min' => 'Le commentaire doit contenir au moins 5 caractères.',
            'comment.max' => 'Le commentaire ne peut pas dépasser 500 caractères.',
            'images.max' => 'Vous ne pouvez pas envoyer plus de 5 photos.',
            'images.*.image' => 'Chaque fichier doit être une image.',
            'images.*.mimes' => 'Les formats acceptés sont : jpeg, jpg, png, gif, webp.',
            'images.*.max' => 'Chaque image ne peut pas dépasser 5 Mo.',
        ];
    }

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