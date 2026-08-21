<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Brand;
use App\Models\Catalog\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    /**
     * Liste des marques
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Brand::where('is_active', true);

            // Uniquement les marques en vedette
            if ($request->has('featured') && $request->featured === 'true') {
                $query->where('is_featured', true);
            }

            // Uniquement les marques avec produits
            if ($request->has('has_products') && $request->has_products === 'true') {
                $query->where('products_count', '>', 0);
            }

            // Filtre par pays
            if ($request->has('country')) {
                $query->where('country', $request->country);
            }

            // Tri
            $sortBy = $request->sort_by ?? 'name';
            $sortDirection = $request->sort_direction ?? 'asc';

            switch ($sortBy) {
                case 'rating':
                    $query->orderBy('average_rating', $sortDirection);
                    break;
                case 'products_count':
                    $query->orderBy('products_count', $sortDirection);
                    break;
                default:
                    $query->orderBy('name', $sortDirection);
            }

            $brands = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Marques récupérées avec succès',
                'data' => $brands,
                'total' => $brands->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des marques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détails d'une marque
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        try {
            $brand = Brand::findOrFail($id);

            // Compter les produits
            $brand->products_count = $brand->products()->count();
            $brand->average_rating = $brand->products()->avg('average_rating') ?? 0;

            return response()->json([
                'success' => true,
                'message' => 'Marque récupérée avec succès',
                'data' => $brand,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Marque introuvable',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Produits d'une marque
     * 
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function products(int $id, Request $request)
    {
        try {
            $brand = Brand::findOrFail($id);

            $query = Product::with(['category', 'images'])
                           ->where('is_active', true)
                           ->where('brand_id', $brand->id);

            // Filtre par catégorie
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

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
                'message' => 'Produits de la marque récupérés',
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
     * Créer une marque (admin)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:150|unique:brands,name',
                'description' => 'nullable|string',
                'logo' => 'nullable|string',
                'banner' => 'nullable|string',
                'website' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:150',
                'phone' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'is_active' => 'sometimes|boolean',
                'is_featured' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $brand = Brand::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name) . '-' . uniqid(),
                'description' => $request->description,
                'logo' => $request->logo,
                'banner' => $request->banner,
                'website' => $request->website,
                'email' => $request->email,
                'phone' => $request->phone,
                'country' => $request->country,
                'is_active' => $request->is_active ?? true,
                'is_featured' => $request->is_featured ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Marque créée avec succès',
                'data' => $brand,
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
     * Mettre à jour une marque (admin)
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        try {
            $brand = Brand::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:150|unique:brands,name,' . $id,
                'description' => 'nullable|string',
                'logo' => 'nullable|string',
                'banner' => 'nullable|string',
                'website' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:150',
                'phone' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'is_active' => 'sometimes|boolean',
                'is_featured' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $brand->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Marque mise à jour',
                'data' => $brand->fresh(),
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
     * Supprimer une marque (admin)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        try {
            $brand = Brand::findOrFail($id);
            $brand->delete();

            return response()->json([
                'success' => true,
                'message' => 'Marque supprimée',
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
     * Statistiques des marques (admin)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        try {
            $totalBrands = Brand::count();
            $activeBrands = Brand::where('is_active', true)->count();
            $featuredBrands = Brand::where('is_featured', true)->count();
            $brandsWithProducts = Brand::where('products_count', '>', 0)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $totalBrands,
                    'active' => $activeBrands,
                    'featured' => $featuredBrands,
                    'with_products' => $brandsWithProducts,
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