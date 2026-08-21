<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Product;
use App\Models\Catalog\Category;
use App\Models\Catalog\Brand;
use App\Models\Catalog\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

// Ajouter les imports
use App\Http\Resources\Api\Product\ProductResource;
use App\Http\Resources\Api\Product\ProductCollection;
use App\Http\Resources\Api\Product\CategoryResource;
use App\Http\Resources\Api\Product\BrandResource;

class ProductController extends Controller
{
    /**
     * Liste paginée des produits
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Product::with(['category', 'brand', 'images'])
                            ->where('is_active', true);

            // Pagination
            $perPage = $request->per_page ?? 15;
            $perPage = min($perPage, 50);

            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Produits récupérés avec succès',
                'data' => ProductResource::collection($products->items()),  // ✅ CORRIGÉ
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'has_more' => $products->hasMorePages(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détails d'un produit
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
   public function show(int $id)
{
    try {
        $product = Product::with([
            'category',
            'brand',
            'images',
            'variants',
            'attributes',
            'specifications',
            'tags',
            'reviews' => function ($query) {
                $query->where('status', 'approved')
                      ->with('user')
                      ->latest();  // ✅ PAS DE take(10)
            },
        ])->findOrFail($id);

        $product->increment('views_count');

        return response()->json([
            'success' => true,
            'message' => 'Produit récupéré avec succès',
            'data' => $product,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Produit introuvable',
            'error' => $e->getMessage(),
        ], 404);
    }
}

    /**
     * Produits en vedette
     */
    public function featured(Request $request)
    {
        try {
            $products = Product::with(['category', 'brand', 'images'])
                              ->where('is_active', true)
                              ->where('is_featured', true)
                              ->take($request->limit ?? 10)
                              ->get();

            return response()->json([
                'success' => true,
                'message' => 'Produits en vedette récupérés',
                'data' => ProductResource::collection($products),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Produits populaires
     */
    public function popular(Request $request)
    {
        try {
            $products = Product::with(['category', 'brand', 'images'])
                              ->where('is_active', true)
                              ->orderBy('sales_count', 'desc')
                              ->orderBy('views_count', 'desc')
                              ->take($request->limit ?? 10)
                              ->get();

            return response()->json([
                'success' => true,
                'message' => 'Produits populaires récupérés',
                'data' => ProductResource::collection($products),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Produits récents
     */
    public function recent(Request $request)
    {
        try {
            $products = Product::with(['category', 'brand', 'images'])
                              ->where('is_active', true)
                              ->latest()
                              ->take($request->limit ?? 10)
                              ->get();

            return response()->json([
                'success' => true,
                'message' => 'Produits récents récupérés',
                'data' => ProductResource::collection($products),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Produits en promotion
     */
    public function onSale(Request $request)
    {
        try {
            $products = Product::with(['category', 'brand', 'images'])
                              ->where('is_active', true)
                              ->where('is_on_sale', true)
                              ->whereNotNull('discount_price')
                              ->take($request->limit ?? 10)
                              ->get();

            return response()->json([
                'success' => true,
                'message' => 'Produits en promotion récupérés',
                'data' => ProductResource::collection($products),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recherche de produits
     */
    public function search(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'q' => 'required|string|min:2|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $query = $request->q;

            $products = Product::with(['category', 'brand', 'images'])
                              ->where('is_active', true)
                              ->where(function ($q) use ($query) {
                                  $q->where('name', 'LIKE', "%{$query}%")
                                    ->orWhere('short_description', 'LIKE', "%{$query}%")
                                    ->orWhere('description', 'LIKE', "%{$query}%")
                                    ->orWhere('sku', 'LIKE', "%{$query}%");
                              })
                              ->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'message' => 'Résultats de recherche',
                'data' => ProductResource::collection($products->items()),
                'pagination' => [
                    'total' => $products->total(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'has_more' => $products->hasMorePages(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Filtres avancés
     */
    public function filter(Request $request)
    {
        try {
            $query = Product::with(['category', 'brand', 'images'])
                            ->where('is_active', true);

            // Filtre par catégorie
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filtre par sous-catégorie
            if ($request->has('subcategory_id')) {
                $query->where('subcategory_id', $request->subcategory_id);
            }

            // Filtre par marque
            if ($request->has('brand_id')) {
                $query->where('brand_id', $request->brand_id);
            }

            // Filtre par prix minimum
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            // Filtre par prix maximum
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Filtre par disponibilité
            if ($request->has('in_stock') && $request->in_stock === 'true') {
                $query->where('stock', '>', 0);
            }

            // Filtre par note minimale
            if ($request->has('min_rating')) {
                $query->where('average_rating', '>=', $request->min_rating);
            }

            // Filtre par promotion
            if ($request->has('on_sale') && $request->on_sale === 'true') {
                $query->where('is_on_sale', true)
                      ->whereNotNull('discount_price');
            }

            // Tri
            $sortBy = $request->sort_by ?? 'created_at';
            $sortDirection = $request->sort_direction ?? 'desc';

            switch ($sortBy) {
                case 'price':
                    $query->orderBy('price', $sortDirection);
                    break;
                case 'name':
                    $query->orderBy('name', $sortDirection);
                    break;
                case 'popularity':
                    $query->orderBy('sales_count', $sortDirection);
                    break;
                case 'rating':
                    $query->orderBy('average_rating', $sortDirection);
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'discount':
                    $query->orderByRaw('(price - COALESCE(discount_price, price)) DESC');
                    break;
                default:
                    $query->orderBy('created_at', $sortDirection);
            }

            $products = $query->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'message' => 'Produits filtrés',
                'data' => ProductResource::collection($products->items()),
                'pagination' => [
                    'total' => $products->total(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'has_more' => $products->hasMorePages(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du filtrage',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Produits par catégorie
     */
    public function byCategory(int $categoryId, Request $request)
    {
        try {
            $products = Product::with(['brand', 'images'])
                              ->where('is_active', true)
                              ->where('category_id', $categoryId)
                              ->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'message' => 'Produits de la catégorie récupérés',
                'data' => ProductResource::collection($products->items()),
                'pagination' => [
                    'total' => $products->total(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Produits par marque
     */
    public function byBrand(int $brandId, Request $request)
    {
        try {
            $products = Product::with(['category', 'images'])
                              ->where('is_active', true)
                              ->where('brand_id', $brandId)
                              ->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'message' => 'Produits de la marque récupérés',
                'data' => ProductResource::collection($products->items()),
                'pagination' => [
                    'total' => $products->total(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Produits similaires
     */
    public function similar(int $id)
    {
        try {
            $product = Product::findOrFail($id);

            $similarProducts = Product::with(['category', 'images'])
                                      ->where('is_active', true)
                                      ->where('id', '!=', $id)
                                      ->where(function ($query) use ($product) {
                                          $query->where('category_id', $product->category_id)
                                                ->orWhere('brand_id', $product->brand_id);
                                      })
                                      ->take(10)
                                      ->get();

            return response()->json([
                'success' => true,
                'message' => 'Produits similaires récupérés',
                'data' => $similarProducts,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer un produit (admin/vendeur)
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'discount_price' => 'nullable|numeric|min:0|lt:price',
                'stock' => 'required|integer|min:0',
                'sku' => 'required|string|unique:products,sku',
                'category_id' => 'required|exists:categories,id',
                'brand_id' => 'nullable|exists:brands,id',
                'subcategory_id' => 'nullable|exists:subcategories,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $product = Product::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name) . '-' . uniqid(),
                'short_description' => $request->short_description,
                'description' => $request->description,
                'price' => $request->price,
                'discount_price' => $request->discount_price,
                'stock' => $request->stock,
                'sku' => $request->sku,
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id,
                'subcategory_id' => $request->subcategory_id,
                'is_active' => $request->is_active ?? true,
                'is_featured' => $request->is_featured ?? false,
                'is_on_sale' => $request->is_on_sale ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès',
                'data' => $product,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un produit
     */
    public function update(Request $request, int $id)
    {
        try {
            $product = Product::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric|min:0',
                'discount_price' => 'nullable|numeric|min:0',
                'stock' => 'sometimes|integer|min:0',
                'sku' => 'sometimes|string|unique:products,sku,' . $id,
                'category_id' => 'sometimes|exists:categories,id',
                'brand_id' => 'nullable|exists:brands,id',
                'is_active' => 'sometimes|boolean',
                'is_featured' => 'sometimes|boolean',
                'is_on_sale' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $product->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Produit mis à jour',
                'data' => $product->fresh(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un produit
     */
    public function destroy(int $id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Produit supprimé',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restaurer un produit supprimé
     */
    public function restore(int $id)
    {
        try {
            $product = Product::withTrashed()->findOrFail($id);
            $product->restore();

            return response()->json([
                'success' => true,
                'message' => 'Produit restauré',
                'data' => $product,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}