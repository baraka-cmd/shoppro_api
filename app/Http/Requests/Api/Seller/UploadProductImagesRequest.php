<?php

namespace App\Http\Requests\Api\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Request : UploadProductImagesRequest
 *
 * Valide l'upload d'images pour un produit.
 *
 * Règles métier :
 *   - Maximum 5 images par upload
 *   - Formats acceptés : JPEG, JPG, PNG, WEBP
 *   - Taille max : 5 Mo par image
 *   - Aucune image ne doit être corrompue
 *
 * Sécurité :
 *   - Le produit doit appartenir au vendeur (vérifié dans authorize)
 *   - Le nombre d'images TOTAL ne doit pas dépasser 10 (5 + 5 existantes)
 *   - Validation MIME stricte (pas seulement l'extension)
 *   - Noms de fichiers uniques (gérés par Laravel storage)
 *
 * @package App\Http\Requests\Api\Seller
 */
class UploadProductImagesRequest extends FormRequest
{
    /**
     * Nombre maximum d'images par upload.
     */
    private const MAX_IMAGES_PER_UPLOAD = 5;

    /**
     * Nombre maximum d'images au total par produit.
     */
    private const MAX_IMAGES_PER_PRODUCT = 10;

    /**
     * Taille maximum par image (5 Mo).
     */
    private const MAX_IMAGE_SIZE_KB = 5120;

    /**
     * Autorise la requête.
     *
     * Vérifie :
     *   - L'utilisateur est vendeur
     *   - Le produit existe et appartient au vendeur
     *   - Le quota d'images n'est pas dépassé
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user || (!$user->isSeller() && !$user->isAdmin())) {
            return false;
        }

        $product = $this->route('product');

        if (!$product) {
            return false;
        }

        // Vérifier la propriété du produit
        if ($user->can('uploadImages', $product)) {
            return true;
        }

        return false;
    }

    /**
     * Règles de validation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'images' => [
                'required',
                'array',
                'min:1',
                'max:' . self::MAX_IMAGES_PER_UPLOAD,
            ],

            'images.*' => [
                'required',
                File::image()
                    ->types(['jpeg', 'jpg', 'png', 'webp'])
                    ->max(self::MAX_IMAGE_SIZE_KB)
                    ->dimensions(
                        Rule::dimensions()
                            ->minWidth(100)
                            ->minHeight(100)
                            ->maxWidth(5000)
                            ->maxHeight(5000)
                    ),
            ],
        ];
    }

    /**
     * Messages personnalisés.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.required' => 'Veuillez sélectionner au moins une image.',
            'images.array'    => 'Le format des images est invalide.',
            'images.min'      => 'Veuillez sélectionner au moins :min image.',
            'images.max'      => 'Vous ne pouvez pas uploader plus de :max images à la fois.',

            'images.*.required'  => 'Chaque image est obligatoire.',
            'images.*.image'     => 'Le fichier doit être une image (JPEG, PNG, WEBP).',
            'images.*.max'       => 'Chaque image ne peut pas dépasser 5 Mo.',
            'images.*.mimes'     => 'Les formats acceptés sont : JPEG, PNG, WEBP.',
            'images.*.dimensions' => 'Chaque image doit mesurer entre 100x100 et 5000x5000 pixels.',
        ];
    }

    /**
     * Noms des attributs.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'images'   => 'images',
            'images.*' => 'image',
        ];
    }

    /**
     * Validation métier après les règles de base.
     *
     * Vérifie :
     *   - Le quota total d'images du produit n'est pas dépassé
     *   - Aucun fichier n'est vide ou corrompu
     */
    public function after(): array
    {
        return [
            function ($validator) {
                $product = $this->route('product');
                $uploadedImages = $this->file('images', []);

                if (!$product) {
                    return;
                }

                // Vérifier le quota total d'images
                $currentImagesCount = $product->images()->count();
                $newImagesCount = is_array($uploadedImages) ? count($uploadedImages) : 0;
                $totalAfterUpload = $currentImagesCount + $newImagesCount;

                if ($totalAfterUpload > self::MAX_IMAGES_PER_PRODUCT) {
                    $remaining = max(0, self::MAX_IMAGES_PER_PRODUCT - $currentImagesCount);
                    $validator->errors()->add(
                        'images',
                        "Vous ne pouvez pas dépasser " . self::MAX_IMAGES_PER_PRODUCT . " images par produit. "
                        . "Actuellement : {$currentImagesCount} images. "
                        . "Places restantes : {$remaining}."
                    );
                }

                // Vérifier que chaque fichier est lisible
                if (is_array($uploadedImages)) {
                    foreach ($uploadedImages as $index => $image) {
                        if (!$image->isValid()) {
                            $validator->errors()->add(
                                "images.{$index}",
                                "L'image " . ($index + 1) . " est corrompue ou invalide."
                            );
                        }
                    }
                }
            },
        ];
    }
}
