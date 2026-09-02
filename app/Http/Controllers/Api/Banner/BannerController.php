<?php

namespace App\Http\Controllers\Api\Banner;

use App\Http\Controllers\Controller;
use App\Models\Marketing\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BannerController extends Controller
{
    /**
     * Liste des bannières actives
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Banner::where('is_active', true);

            // Filtre par position
            if ($request->has('position')) {
                $query->where('position', $request->position);
            }

            // Filtre par type de cible
            if ($request->has('target_type')) {
                $query->where('target_type', $request->target_type);
            }

            // Tri
            $sortBy = $request->sort_by ?? 'sort_order';
            $sortDirection = $request->sort_direction ?? 'asc';

            $banners = $query->orderBy($sortBy, $sortDirection)->get();

            // Formater les URLs d'images
            $banners = $banners->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'subtitle' => $banner->subtitle,
                    'description' => $banner->description,
                    'image' => $banner->image 
                        ? asset('storage/' . $banner->image) 
                        : null,
                    'image_mobile' => $banner->image_mobile 
                        ? asset('storage/' . $banner->image_mobile) 
                        : null,
                    'thumbnail' => $banner->thumbnail 
                        ? asset('storage/' . $banner->thumbnail) 
                        : null,
                    'target_type' => $banner->target_type,
                    'target_id' => $banner->target_id,
                    'external_url' => $banner->external_url,
                    'position' => $banner->position,
                    'sort_order' => $banner->sort_order,
                    'is_active' => $banner->is_active,
                    'is_animated' => $banner->is_animated,
                    'start_date' => $banner->start_date?->format('Y-m-d'),
                    'end_date' => $banner->end_date?->format('Y-m-d'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Bannières récupérées avec succès',
                'data' => $banners,
                'total' => $banners->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des bannières',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détails d'une bannière
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        try {
            $banner = Banner::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Bannière récupérée',
                'data' => [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'subtitle' => $banner->subtitle,
                    'description' => $banner->description,
                    'image' => $banner->image 
                        ? asset('storage/' . $banner->image) 
                        : null,
                    'target_type' => $banner->target_type,
                    'target_id' => $banner->target_id,
                    'external_url' => $banner->external_url,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bannière introuvable',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Créer une bannière (admin)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:150',
                'subtitle' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|string',
                'target_type' => 'required|in:product,category,promotion,external_url,none',
                'target_id' => 'nullable|string',
                'external_url' => 'nullable|string|max:255',
                'position' => 'required|in:home_top,home_middle,home_bottom,category_page,product_page,sidebar',
                'sort_order' => 'sometimes|integer',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'is_active' => 'sometimes|boolean',
                'is_animated' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $banner = Banner::create([
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'description' => $request->description,
                'image' => $request->image,
                'target_type' => $request->target_type,
                'target_id' => $request->target_id,
                'external_url' => $request->external_url,
                'position' => $request->position,
                'sort_order' => $request->sort_order ?? 0,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->is_active ?? true,
                'is_animated' => $request->is_animated ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bannière créée avec succès',
                'data' => $banner,
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
     * Mettre à jour une bannière (admin)
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        try {
            $banner = Banner::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:150',
                'subtitle' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|string',
                'target_type' => 'sometimes|in:product,category,promotion,external_url,none',
                'target_id' => 'nullable|string',
                'external_url' => 'nullable|string|max:255',
                'position' => 'sometimes|in:home_top,home_middle,home_bottom,category_page,product_page,sidebar',
                'sort_order' => 'sometimes|integer',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'is_active' => 'sometimes|boolean',
                'is_animated' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $banner->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Bannière mise à jour',
                'data' => $banner->fresh(),
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
     * Supprimer une bannière (admin)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        try {
            $banner = Banner::findOrFail($id);
            $banner->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bannière supprimée',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}