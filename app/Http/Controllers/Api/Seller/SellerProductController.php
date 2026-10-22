<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Seller\StoreSellerProductRequest;
use App\Http\Requests\Api\Seller\UpdateSellerProductRequest;
use App\Http\Requests\Api\Seller\UploadProductImagesRequest;
use App\Http\Resources\Api\Seller\SellerProductResource;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Controller : SellerProductController
 *
 * CRUD complet des produits pour un vendeur.
 *
 * Sécurité (défense en profondeur) :
 *   - Middleware : auth:sanctum + role:seller,admin + seller.active
 *   - Policies : ProductPolicy appliquée sur chaque action
 *   - Scope strict : product.seller_id === auth()->id()
 *   - Validation : StoreSellerProductRequest, UpdateSellerProductRequest
 *   - Aucune fuite de données admin (cost_price masqué)
 *
 * @package App\Http\Controllers\Api\Seller
 */
class SellerProductController extends Controller
{
    /**
     * Nombre d'images maximum par produit.
     */
    private const MAX_IMAGES_PER_PRODUCT = 10;

    // ============================================
    // INDEX — LISTE DES PRODUITS
    // ============================================

    /**
     * GET /api/v1/seller/products
     *
     * Liste paginée des produits du vendeur connecté.
     *
     * Query params :
     *   - search       : recherche par nom ou SKU
     *   - category_id  : filtre par catégorie
     *   - status       : all | draft | active | archived | out_of_stock
     *   - sort_by      : created_at | price | sales_count | views_count
     *   - sort_dir     : asc | desc
     *   - per_page     : nombre par page (défaut 15, max 50)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);

            $query = Product::query()
                ->where('seller_id', $sellerId)
                ->with(['category', 'brand', 'images']);

            // ============================================
            // FILTRE : Recherche
            // ============================================
            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // ============================================
            // FILTRE : Catégorie
            // ============================================
            if ($request->filled('category_id')) {
                $query->where('category_id', (int) $request->input('category_id'));
            }

            // ============================================
            // FILTRE : Statut
            // ============================================
            $status = $request->input('status', 'all');
            switch ($status) {
                case 'draft':
                    $query->where('is_active', false)->whereNull('deleted_at');
                    break;
                case 'active':
                    $query->where('is_active', true)->whereNull('deleted_at');
                    break;
                case 'archived':
                    $query->onlyTrashed();
                    break;
                case 'out_of_stock':
                    $query->where('stock', '<=', 0)->whereNull('deleted_at');
                    break;
                case 'low_stock':
                    $query->where('stock', '>', 0)
                          ->whereColumn('stock', '<=', 'min_stock')
                          ->whereNull('deleted_at');
                    break;
                // 'all' → pas de filtre
            }

            // ============================================
            // TRI
            // ============================================
            $sortBy = $request->input('sort_by', 'created_at');
            $sortDir = $request->input('sort_dir', 'desc');

            $allowedSorts = ['created_at', 'price', 'sales_count', 'views_count', 'stock'];
            if (!in_array($sortBy, $allowedSorts, true)) {
                $sortBy = 'created_at';
            }

            $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortBy, $sortDir);

            // ============================================
            // PAGINATION
            // ============================================
            $perPage = min((int) $request->input('per_page', 15), 50);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Produits récupérés avec succès.',
                'data'    => SellerProductResource::collection($products),
                'pagination' => [
                    'total'        => $products->total(),
                    'count'        => $products->count(),
                    'per_page'     => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'has_more'     => $products->hasMorePages(),
                ],
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerProductController::index', ['error' => $e->getMessage()]);
            return $this->error('Impossible de récupérer les produits : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // STORE — CRÉATION
    // ============================================

    /**
     * POST /api/v1/seller/products
     *
     * Crée un nouveau produit en statut "brouillon" (is_active = false).
     */
    public function store(StoreSellerProductRequest $request): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);

            DB::beginTransaction();

            $product = Product::create([
                // Champs validés
                ...$request->validated(),

                // Champs forcés (jamais acceptés du client)
                'seller_id'      => $sellerId,
                'slug'           => $this->generateUniqueSlug($request->input('name')),
                'is_active'      => false,   // Toujours draft à la création
                'is_available'   => true,
                'is_new'         => true,    // Nouveau produit
                'views_count'    => 0,
                'sales_count'    => 0,
                'favorites_count'=> 0,
                'reviews_count'  => 0,
                'average_rating' => 0,
            ]);

            DB::commit();

            Log::info('Produit créé', [
                'product_id' => $product->id,
                'seller_id'  => $sellerId,
                'name'       => $product->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès. Il est en brouillon, vous pouvez maintenant ajouter des images et le publier.',
                'data'    => (new SellerProductResource($product->load(['category', 'brand', 'images'])))->withDetail(),
            ], Response::HTTP_CREATED);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerProductController::store', ['error' => $e->getMessage()]);
            return $this->error('Impossible de créer le produit : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // SHOW — DÉTAIL
    // ============================================

    /**
     * GET /api/v1/seller/products/{product}
     *
     * Affiche le détail d'un produit appartenant au vendeur.
     */
    public function show(Request $request, Product $product): JsonResponse
    {
        try {
            // Vérifier la propriété (via Policy)
            if (!$request->user()->can('view', $product)) {
                return $this->error('Vous n\'êtes pas autorisé à voir ce produit.', Response::HTTP_FORBIDDEN);
            }

            $product->load(['category', 'brand', 'images', 'variants']);

            return response()->json([
                'success' => true,
                'message' => 'Produit récupéré avec succès.',
                'data'    => (new SellerProductResource($product))->withDetail(),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerProductController::show', ['error' => $e->getMessage()]);
            return $this->error('Impossible de récupérer le produit : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // UPDATE — MODIFICATION
    // ============================================

    /**
     * PUT /api/v1/seller/products/{product}
     *
     * Met à jour un produit existant.
     * Fonctionne en mode PATCH partiel grâce à "sometimes".
     */
    public function update(UpdateSellerProductRequest $request, Product $product): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // Si le nom change, régénérer le slug
            if (isset($data['name']) && $data['name'] !== $product->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $product->id);
            }

            $product->update($data);

            DB::commit();

            Log::info('Produit mis à jour', [
                'product_id' => $product->id,
                'seller_id'  => $product->seller_id,
                'fields'     => array_keys($data),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Produit mis à jour avec succès.',
                'data'    => (new SellerProductResource($product->fresh()->load(['category', 'brand', 'images'])))->withDetail(),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerProductController::update', ['error' => $e->getMessage()]);
            return $this->error('Impossible de mettre à jour le produit : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // DESTROY — ARCHIVAGE (SOFT DELETE)
    // ============================================

    /**
     * DELETE /api/v1/seller/products/{product}
     *
     * Archive un produit (soft delete).
     * Le produit n'apparaîtra plus côté client mais reste en base.
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        try {
            if (!$request->user()->can('delete', $product)) {
                return $this->error('Vous n\'êtes pas autorisé à supprimer ce produit.', Response::HTTP_FORBIDDEN);
            }

            // Empêcher l'archivage d'un produit déjà publié qui a des ventes
            if ($product->sales_count > 0) {
                return $this->error(
                    'Impossible d\'archiver un produit déjà vendu. Vous pouvez le dépublier à la place.',
                    Response::HTTP_CONFLICT
                );
            }

            $product->delete();

            Log::info('Produit archivé', [
                'product_id' => $product->id,
                'seller_id'  => $product->seller_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Produit archivé avec succès.',
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerProductController::destroy', ['error' => $e->getMessage()]);
            return $this->error('Impossible d\'archiver le produit : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // PUBLISH — PUBLICATION
    // ============================================

    /**
     * POST /api/v1/seller/products/{product}/publish
     *
     * Publie un produit (draft → active).
     *
     * Conditions :
     *   - KYC validé (vérifié dans ProductPolicy::publish)
     *   - Produit appartient au vendeur
     *   - Prix > 0
     *   - Catégorie définie
     *   - Au moins 1 image
     *   - Stock >= 0 (peut être publié en rupture)
     */
    public function publish(Request $request, Product $product): JsonResponse
    {
        try {
            if (!$request->user()->can('publish', $product)) {
                return $this->error(
                    'Vous n\'êtes pas autorisé à publier ce produit. Vérifiez votre KYC et complétez les informations.',
                    Response::HTTP_FORBIDDEN
                );
            }

            // Vérifications métier supplémentaires
            if ($product->price <= 0) {
                return $this->error('Le prix du produit doit être supérieur à 0.', Response::HTTP_CONFLICT);
            }

            if (!$product->category_id) {
                return $this->error('La catégorie du produit est obligatoire.', Response::HTTP_CONFLICT);
            }

            if (!$product->images()->exists()) {
                return $this->error('Ajoutez au moins une image avant de publier.', Response::HTTP_CONFLICT);
            }

            $product->update([
                'is_active' => true,
                'is_new'    => true,
            ]);

            Log::info('Produit publié', [
                'product_id' => $product->id,
                'seller_id'  => $product->seller_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Produit publié avec succès. Il est maintenant visible par les clients.',
                'data'    => (new SellerProductResource($product->fresh()->load(['category', 'brand', 'images'])))->withDetail(),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerProductController::publish', ['error' => $e->getMessage()]);
            return $this->error('Impossible de publier le produit : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // UNPUBLISH — DÉPUBLICATION
    // ============================================

    /**
     * POST /api/v1/seller/products/{product}/unpublish
     *
     * Dépublie un produit (active → draft).
     */
    public function unpublish(Request $request, Product $product): JsonResponse
    {
        try {
            if (!$request->user()->can('unpublish', $product)) {
                return $this->error('Vous n\'êtes pas autorisé à dépublier ce produit.', Response::HTTP_FORBIDDEN);
            }

            $product->update(['is_active' => false]);

            Log::info('Produit dépublié', [
                'product_id' => $product->id,
                'seller_id'  => $product->seller_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Produit dépublié avec succès. Il n\'est plus visible par les clients.',
                'data'    => (new SellerProductResource($product->fresh()))->withDetail(),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerProductController::unpublish', ['error' => $e->getMessage()]);
            return $this->error('Impossible de dépublier le produit : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // DUPLICATE — DUPLICATION
    // ============================================

    /**
     * POST /api/v1/seller/products/{product}/duplicate
     *
     * Duplique un produit existant en nouveau brouillon.
     * Utile pour créer des variantes rapidement.
     */
    public function duplicate(Request $request, Product $product): JsonResponse
    {
        try {
            if (!$request->user()->can('duplicate', $product)) {
                return $this->error('Vous n\'êtes pas autorisé à dupliquer ce produit.', Response::HTTP_FORBIDDEN);
            }

            DB::beginTransaction();

            $newName = $product->name . ' (copie)';
            $newSku  = $this->generateUniqueSku($product->sku . '-COPY');

            $newProduct = $product->replicate();
            $newProduct->name        = $newName;
            $newProduct->slug        = $this->generateUniqueSlug($newName);
            $newProduct->sku         = $newSku;
            $newProduct->barcode     = null;
            $newProduct->is_active   = false;
            $newProduct->is_new      = true;
            $newProduct->is_featured = false;
            $newProduct->is_bestseller = false;
            $newProduct->views_count = 0;
            $newProduct->sales_count = 0;
            $newProduct->favorites_count = 0;
            $newProduct->reviews_count = 0;
            $newProduct->average_rating = 0;
            $newProduct->created_at = now();
            $newProduct->updated_at = now();
            $newProduct->save();

            DB::commit();

            Log::info('Produit dupliqué', [
                'source_id' => $product->id,
                'new_id'    => $newProduct->id,
                'seller_id' => $product->seller_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Produit dupliqué avec succès. Le duplicata est en brouillon.',
                'data'    => (new SellerProductResource($newProduct->load(['category', 'brand', 'images'])))->withDetail(),
            ], Response::HTTP_CREATED);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerProductController::duplicate', ['error' => $e->getMessage()]);
            return $this->error('Impossible de dupliquer le produit : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // PREVIEW — APERÇU CLIENT
    // ============================================

    /**
     * GET /api/v1/seller/products/{product}/preview
     *
     * Retourne le produit tel qu'il apparaîtra côté client.
     */
    public function preview(Request $request, Product $product): JsonResponse
    {
        try {
            if (!$request->user()->can('view', $product)) {
                return $this->error('Vous n\'êtes pas autorisé à voir ce produit.', Response::HTTP_FORBIDDEN);
            }

            $product->load(['category', 'brand', 'images']);

            return response()->json([
                'success' => true,
                'message' => 'Aperçu du produit (côté client).',
                'data'    => (new SellerProductResource($product))->withDetail(),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerProductController::preview', ['error' => $e->getMessage()]);
            return $this->error('Impossible de générer l\'aperçu : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // UPLOAD IMAGES
    // ============================================

    /**
     * POST /api/v1/seller/products/{product}/images
     *
     * Upload une ou plusieurs images pour un produit.
     * Max 5 par upload, 10 au total.
     */
    public function uploadImages(UploadProductImagesRequest $request, Product $product): JsonResponse
    {
        try {
            if (!$request->user()->can('uploadImages', $product)) {
                return $this->error('Vous n\'êtes pas autorisé à modifier les images de ce produit.', Response::HTTP_FORBIDDEN);
            }

            DB::beginTransaction();

            $images = $request->file('images');
            $currentCount = $product->images()->count();
            $uploaded = [];

            // Position initiale
            $position = $product->images()->max('position') ?? -1;

            foreach ($images as $index => $image) {
                $position++;

                // Stocker le fichier
                $path = $image->store(
                    "sellers/{$product->seller_id}/products/{$product->id}",
                    'public'
                );

                // Créer l'entrée en base
                $productImage = $product->images()->create([
                    'image_path'    => $path,
                    'thumbnail_path'=> $path, // À améliorer plus tard avec intervention/image
                    'alt_text'      => $product->name,
                    'position'      => $position,
                    'is_primary'    => $currentCount === 0 && $index === 0, // 1ère image = principale
                    'is_active'     => true,
                    'type'          => 'image',
                ]);

                // Si c'est la 1ère image, la mettre comme image principale du produit
                if ($productImage->is_primary) {
                    $product->update(['main_image' => $path]);
                }

                $uploaded[] = $productImage;
            }

            DB::commit();

            Log::info('Images uploadées', [
                'product_id' => $product->id,
                'count'      => count($uploaded),
            ]);

            return response()->json([
                'success' => true,
                'message' => count($uploaded) . ' image(s) uploadée(s) avec succès.',
                'data'    => (new SellerProductResource($product->fresh()->load(['category', 'brand', 'images'])))->withDetail(),
            ], Response::HTTP_CREATED);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerProductController::uploadImages', ['error' => $e->getMessage()]);
            return $this->error('Impossible d\'uploader les images : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // DELETE IMAGE
    // ============================================

    /**
     * DELETE /api/v1/seller/products/{product}/images/{image}
     *
     * Supprime une image d'un produit.
     */
    public function deleteImage(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        try {
            if (!$request->user()->can('deleteImage', $product)) {
                return $this->error('Vous n\'êtes pas autorisé à supprimer cette image.', Response::HTTP_FORBIDDEN);
            }

            // Vérifier que l'image appartient bien au produit
            if ($image->product_id !== $product->id) {
                return $this->error('Cette image n\'appartient pas à ce produit.', Response::HTTP_FORBIDDEN);
            }

            DB::beginTransaction();

            // Supprimer le fichier physique
            if (Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }

            $wasPrimary = $image->is_primary;

            // Supprimer en base
            $image->delete();

            // Si c'était l'image principale, promouvoir une autre image
            if ($wasPrimary) {
                $newPrimary = $product->images()->orderBy('position')->first();
                if ($newPrimary) {
                    $newPrimary->update(['is_primary' => true]);
                    $product->update(['main_image' => $newPrimary->image_path]);
                } else {
                    $product->update(['main_image' => null]);
                }
            }

            DB::commit();

            Log::info('Image supprimée', [
                'product_id' => $product->id,
                'image_id'   => $image->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Image supprimée avec succès.',
                'data'    => (new SellerProductResource($product->fresh()->load(['category', 'brand', 'images'])))->withDetail(),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerProductController::deleteImage', ['error' => $e->getMessage()]);
            return $this->error('Impossible de supprimer l\'image : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // REORDER IMAGES
    // ============================================

    /**
     * POST /api/v1/seller/products/{product}/images/reorder
     *
     * Réorganise les images d'un produit.
     * Body : { "image_ids": [3, 1, 2] } (ordre désiré)
     */
    public function reorderImages(Request $request, Product $product): JsonResponse
    {
        try {
            if (!$request->user()->can('uploadImages', $product)) {
                return $this->error('Vous n\'êtes pas autorisé à modifier les images de ce produit.', Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'image_ids'   => 'required|array|min:1',
                'image_ids.*' => 'required|integer|exists:product_images,id',
            ], [
                'image_ids.required'   => 'La liste des IDs d\'images est obligatoire.',
                'image_ids.array'      => 'Le format est invalide.',
                'image_ids.*.exists'   => 'Une des images n\'existe pas.',
            ]);

            DB::beginTransaction();

            foreach ($validated['image_ids'] as $position => $imageId) {
                ProductImage::where('id', $imageId)
                    ->where('product_id', $product->id)
                    ->update(['position' => $position]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Images réorganisées avec succès.',
                'data'    => (new SellerProductResource($product->fresh()->load(['images'])))->withDetail(),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerProductController::reorderImages', ['error' => $e->getMessage()]);
            return $this->error('Impossible de réorganiser les images : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // HELPERS PRIVÉS
    // ============================================

    /**
     * Résout l'ID du vendeur cible.
     * Admin peut passer ?seller_id=X, vendeur = son propre ID.
     */
    private function resolveSellerId(Request $request): int
    {
        $user = $request->user();

        if ($user->isAdmin() && $request->has('seller_id')) {
            return (int) $request->input('seller_id');
        }

        return $user->id;
    }

    /**
     * Génère un slug unique.
     */
    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 1;

        $query = Product::withTrashed()->where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        while ($query->exists()) {
            $slug = $baseSlug . '-' . $suffix;

            $query = Product::withTrashed()->where('slug', $slug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }

            $suffix++;
        }

        return $slug;
    }

    /**
     * Génère un SKU unique.
     */
    private function generateUniqueSku(string $baseSku): string
    {
        $sku = $baseSku;
        $suffix = 1;

        while (Product::withTrashed()->where('sku', $sku)->exists()) {
            $sku = $baseSku . '-' . $suffix;
            $suffix++;
        }

        return $sku;
    }

    /**
     * Réponse erreur standardisée.
     */
    private function error(string $message, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}