<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Support\FaqCategoryResource;
use App\Models\Support\FaqCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqCategoryController extends Controller
{
    /**
     * Afficher la liste des catégories de FAQ.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $categories = FaqCategory::active()
                ->ordered()
                ->withCount(['faqs' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Catégories de FAQ récupérées avec succès',
                'data' => FaqCategoryResource::collection($categories),
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
     * Afficher une catégorie spécifique.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = FaqCategory::active()
                ->with(['faqs' => function ($query) {
                    $query->active()->ordered();
                }])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Catégorie récupérée avec succès',
                'data' => new FaqCategoryResource($category),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie introuvable',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}