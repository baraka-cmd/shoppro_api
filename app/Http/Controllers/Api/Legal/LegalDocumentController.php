<?php

namespace App\Http\Controllers\Api\Legal;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Legal\LegalDocumentResource;
use App\Models\Legal\LegalDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegalDocumentController extends Controller
{
    /**
     * Afficher un document légal par son slug.
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $document = LegalDocument::active()
                ->published()
                ->where('slug', $slug)
                ->first();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document introuvable',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document récupéré avec succès',
                'data' => new LegalDocumentResource($document),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher un document légal par son type.
     *
     * @param string $type
     * @return JsonResponse
     */
    public function showByType(string $type): JsonResponse
    {
        try {
            $document = LegalDocument::active()
                ->published()
                ->byType($type)
                ->latest()
                ->first();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document introuvable',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document récupéré avec succès',
                'data' => new LegalDocumentResource($document),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher tous les documents légaux actifs.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $documents = LegalDocument::active()
                ->published()
                ->orderBy('published_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Documents légaux récupérés avec succès',
                'data' => LegalDocumentResource::collection($documents),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des documents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}