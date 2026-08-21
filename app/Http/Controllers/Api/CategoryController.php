<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Category;
use App\Models\Catalog\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Liste des catégories
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
{
    try {
        $query = Category::with('subcategories')
                        ->withCount('products')  // ✅ Compteur
                        ->where('is_active', true);

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        if ($request->has('root') && $request->root === 'true') {
            $query->whereNull('parent_id');
        }

        if ($request->has('featured') && $request->featured === 'true') {
            $query->where('is_featured', true);
        }

        $sortBy = $request->sort_by ?? 'position';
        $sortDirection = $request->sort_direction ?? 'asc';

        $categories = $query->orderBy($sortBy, $sortDirection)->get();

        return response()->json([
            'success' => true,
            'message' => 'Catégories récupérées avec succès',
            'data' => $categories,
            'total' => $categories->count(),
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des catégories',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Détails d'une catégorie
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        try {
            $category = Category::with(['subcategories', 'children'])
                               ->findOrFail($id);

            // Compter les produits
            $category->products_count = $category->products()->count();

            return response()->json([
                'success' => true,
                'message' => 'Catégorie récupérée avec succès',
                'data' => $category,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie introuvable',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Produits d'une catégorie
     * 
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function products(int $id, Request $request)
    {
        try {
            $category = Category::findOrFail($id);

            $query = Product::with(['brand', 'images'])
                           ->where('is_active', true)
                           ->where('category_id', $category->id);

            // Filtre par prix
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Tri
            $sortBy = $request->sort_by ?? 'created_at';
            $sortDirection = $request->sort_direction ?? 'desc';

            $products = $query->orderBy($sortBy, $sortDirection)
                              ->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'message' => 'Produits de la catégorie récupérés',
                'data' => $products->items(),
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
                'message' => 'Erreur lors de la récupération',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sous-catégories d'une catégorie
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function subcategories(int $id)
    {
        try {
            $category = Category::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Sous-catégories récupérées',
                'data' => $category->subcategories()->where('is_active', true)->get(),
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
     * Créer une catégorie (admin)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:150',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|string',
                'icon' => 'nullable|string|max:100',
                'is_active' => 'sometimes|boolean',
                'is_featured' => 'sometimes|boolean',
                'position' => 'sometimes|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $category = Category::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name) . '-' . uniqid(),
                'description' => $request->description,
                'parent_id' => $request->parent_id,
                'image' => $request->image,
                'icon' => $request->icon,
                'is_active' => $request->is_active ?? true,
                'is_featured' => $request->is_featured ?? false,
                'position' => $request->position ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Catégorie créée avec succès',
                'data' => $category,
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
     * Mettre à jour une catégorie (admin)
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        try {
            $category = Category::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:150',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|string',
                'icon' => 'nullable|string|max:100',
                'is_active' => 'sometimes|boolean',
                'is_featured' => 'sometimes|boolean',
                'position' => 'sometimes|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $category->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Catégorie mise à jour',
                'data' => $category->fresh(),
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
     * Supprimer une catégorie (admin)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Catégorie supprimée',
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
     * Statistiques des catégories (admin)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        try {
            $totalCategories = Category::count();
            $activeCategories = Category::where('is_active', true)->count();
            $featuredCategories = Category::where('is_featured', true)->count();
            $rootCategories = Category::whereNull('parent_id')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $totalCategories,
                    'active' => $activeCategories,
                    'featured' => $featuredCategories,
                    'root' => $rootCategories,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors des statistiques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}