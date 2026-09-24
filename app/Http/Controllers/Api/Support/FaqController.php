<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Support\FaqResource;
use App\Models\Support\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Afficher la liste des FAQs.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Faq::active()
                ->with('category')
                ->ordered();

            // Filtre par catégorie
            if ($request->has('category_id')) {
                $query->where('faq_category_id', $request->category_id);
            }

            // Filtre par slug de catégorie
            if ($request->has('category')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', $request->category);
                });
            }

            // Filtre : uniquement les populaires
            if ($request->boolean('popular')) {
                $query->popular();
            }

            // Recherche
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('question', 'LIKE', "%{$search}%")
                      ->orWhere('answer', 'LIKE', "%{$search}%");
                });
            }

            $faqs = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'FAQs récupérées avec succès',
                'data' => FaqResource::collection($faqs),
                'total' => $faqs->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des FAQs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher une FAQ spécifique.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $faq = Faq::active()
                ->with('category')
                ->findOrFail($id);

            // Incrémenter le compteur de vues
            $faq->incrementViews();

            return response()->json([
                'success' => true,
                'message' => 'FAQ récupérée avec succès',
                'data' => new FaqResource($faq),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ introuvable',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Marquer une FAQ comme utile.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function markHelpful(int $id): JsonResponse
    {
        try {
            $faq = Faq::active()->findOrFail($id);
            $faq->incrementHelpful();

            return response()->json([
                'success' => true,
                'message' => 'Merci pour votre retour !',
                'data' => [
                    'helpful_count' => $faq->helpful_count,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ introuvable',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}