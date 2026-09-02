<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review\Review;
use App\Models\Review\ReviewImage;
use App\Models\Catalog\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReviewController extends Controller
{
    /**
     * Récupérer la liste paginée des avis.
     *
     * Endpoint : GET /api/v1/admin/reviews
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $page = $request->query('page', 1);

            $query = Review::with(['user', 'product', 'images'])
                ->orderByDesc('created_at');

            // Filtres
            if ($request->has('status') && !empty($request->query('status'))) {
                $query->where('status', $request->query('status'));
            }

            if ($request->has('rating')) {
                $query->where('rating', $request->query('rating'));
            }

            if ($request->has('product_id')) {
                $query->where('product_id', $request->query('product_id'));
            }

            if ($request->has('user_id')) {
                $query->where('user_id', $request->query('user_id'));
            }

            if ($request->has('search') && !empty($request->query('search'))) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('content', 'LIKE', "%{$search}%")
                      ->orWhereHas('user', function ($uq) use ($search) {
                          $uq->where('name', 'LIKE', "%{$search}%");
                      });
                });
            }

            // Tri
            $sortBy = $request->query('sort_by', 'created_at');
            $sortDirection = $request->query('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $reviews = $query->paginate($perPage, ['*'], 'page', $page);

            $data = $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'title' => $review->title,
                    'content' => $review->content,
                    'rating' => (int) $review->rating,
                    'quality_rating' => $review->quality_rating,
                    'price_rating' => $review->price_rating,
                    'shipping_rating' => $review->shipping_rating,
                    'status' => $review->status,
                    'is_verified_purchase' => (bool) $review->is_verified_purchase,
                    'is_recommended' => (bool) $review->is_recommended,
                    'helpful_count' => (int) $review->helpful_count,
                    'not_helpful_count' => (int) $review->not_helpful_count,
                    'views_count' => (int) $review->views_count,
                    'admin_response' => $review->admin_response,
                    'admin_response_at' => $review->admin_response_at ? $review->admin_response_at->toIso8601String() : null,
                    'approved_at' => $review->approved_at ? $review->approved_at->toIso8601String() : null,
                    'created_at' => $review->created_at->toIso8601String(),
                    'updated_at' => $review->updated_at->toIso8601String(),
                    'user' => $review->user ? [
                        'id' => $review->user->id,
                        'name' => $review->user->name,
                        'email' => $review->user->email,
                        'avatar' => $review->user->avatar ?? null,
                    ] : null,
                    'product' => $review->product ? [
                        'id' => $review->product->id,
                        'name' => $review->product->name,
                        'image' => $review->product->main_image ?? null,
                    ] : null,
                    'images' => $review->images->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'path' => $image->image_path,
                            'type' => $image->type,
                        ];
                    })->toArray(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Avis récupérés avec succès',
                'data' => $data,
                'pagination' => [
                    'total' => $reviews->total(),
                    'count' => $reviews->count(),
                    'per_page' => $reviews->perPage(),
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'has_more' => $reviews->hasMorePages(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des avis',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les détails d'un avis.
     *
     * Endpoint : GET /api/v1/admin/reviews/{id}
     */
    public function show($id)
    {
        try {
            $review = Review::with(['user', 'product', 'images', 'votes'])
                ->find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avis introuvable',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Avis récupéré avec succès',
                'data' => $review,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'avis',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les avis en attente de modération.
     *
     * Endpoint : GET /api/v1/admin/reviews/pending
     */
    public function pending(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);

            $reviews = Review::with(['user', 'product'])
                ->where('status', 'pending')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Avis en attente récupérés',
                'data' => $reviews->items(),
                'pagination' => [
                    'total' => $reviews->total(),
                    'count' => $reviews->count(),
                    'per_page' => $reviews->perPage(),
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'has_more' => $reviews->hasMorePages(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des avis en attente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les avis approuvés.
     *
     * Endpoint : GET /api/v1/admin/reviews/approved
     */
    public function approved(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);

            $reviews = Review::with(['user', 'product'])
                ->where('status', 'approved')
                ->orderByDesc('approved_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Avis approuvés récupérés',
                'data' => $reviews->items(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des avis approuvés',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les avis rejetés.
     *
     * Endpoint : GET /api/v1/admin/reviews/rejected
     */
    public function rejected(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);

            $reviews = Review::with(['user', 'product'])
                ->where('status', 'rejected')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Avis rejetés récupérés',
                'data' => $reviews->items(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des avis rejetés',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les avis spam.
     *
     * Endpoint : GET /api/v1/admin/reviews/spam
     */
    public function spam(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);

            $reviews = Review::with(['user', 'product'])
                ->where('status', 'spam')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Avis spam récupérés',
                'data' => $reviews->items(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des avis spam',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les avis d'un produit.
     *
     * Endpoint : GET /api/v1/admin/reviews/by-product/{productId}
     */
    public function byProduct(Request $request, $productId)
    {
        try {
            $perPage = $request->query('per_page', 20);

            $reviews = Review::with('user')
                ->where('product_id', $productId)
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Avis du produit récupérés',
                'data' => $reviews->items(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des avis du produit',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les avis d'un utilisateur.
     *
     * Endpoint : GET /api/v1/admin/reviews/by-user/{userId}
     */
    public function byUser($userId)
    {
        try {
            $reviews = Review::with('product')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Avis de l\'utilisateur récupérés',
                'data' => $reviews,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des avis de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approuver un avis.
     *
     * Endpoint : POST /api/v1/admin/reviews/{id}/approve
     */
    public function approve($id)
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avis introuvable',
                ], 404);
            }

            $review->status = 'approved';
            $review->approved_at = now();
            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Avis approuvé avec succès',
                'data' => $review,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'approbation de l\'avis',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rejeter un avis.
     *
     * Endpoint : POST /api/v1/admin/reviews/{id}/reject
     */
    public function reject(Request $request, $id)
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avis introuvable',
                ], 404);
            }

            $review->status = 'rejected';
            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Avis rejeté avec succès',
                'data' => $review,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet de l\'avis',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marquer un avis comme spam.
     *
     * Endpoint : POST /api/v1/admin/reviews/{id}/mark-spam
     */
    public function markAsSpam($id)
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avis introuvable',
                ], 404);
            }

            $review->status = 'spam';
            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Avis marqué comme spam',
                'data' => $review,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage comme spam',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remettre un avis en attente.
     *
     * Endpoint : POST /api/v1/admin/reviews/{id}/reset
     */
    public function reset($id)
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avis introuvable',
                ], 404);
            }

            $review->status = 'pending';
            $review->approved_at = null;
            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Avis remis en attente',
                'data' => $review,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la remise en attente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Répondre à un avis.
     *
     * Endpoint : POST /api/v1/admin/reviews/{id}/respond
     */
    public function respond(Request $request, $id)
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avis introuvable',
                ], 404);
            }

            $review->admin_response = $request->input('response');
            $review->admin_response_at = now();
            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Réponse envoyée avec succès',
                'data' => $review,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la réponse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Modifier la réponse admin.
     *
     * Endpoint : PUT /api/v1/admin/reviews/{id}/respond
     */
    public function updateResponse(Request $request, $id)
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avis introuvable',
                ], 404);
            }

            $review->admin_response = $request->input('response');
            $review->admin_response_at = now();
            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Réponse modifiée avec succès',
                'data' => $review,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la réponse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer la réponse admin.
     *
     * Endpoint : DELETE /api/v1/admin/reviews/{id}/respond
     */
    public function deleteResponse($id)
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avis introuvable',
                ], 404);
            }

            $review->admin_response = null;
            $review->admin_response_at = null;
            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Réponse supprimée avec succès',
                'data' => $review,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la réponse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marquer un avis comme recommandé.
     *
     * Endpoint : POST /api/v1/admin/reviews/{id}/recommend
     */
    public function recommend($id)
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avis introuvable',
                ], 404);
            }

            $review->is_recommended = true;
            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Avis recommandé',
                'data' => $review,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recommandation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retirer la recommandation d'un avis.
     *
     * Endpoint : DELETE /api/v1/admin/reviews/{id}/recommend
     */
    public function unrecommend($id)
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avis introuvable',
                ], 404);
            }

            $review->is_recommended = false;
            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Recommandation retirée',
                'data' => $review,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retrait de la recommandation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les statistiques des avis.
     *
     * Endpoint : GET /api/v1/admin/reviews/stats/count
     */
    public function statsCount()
    {
        try {
            $total = Review::count();
            $pending = Review::where('status', 'pending')->count();
            $approved = Review::where('status', 'approved')->count();
            $rejected = Review::where('status', 'rejected')->count();
            $spam = Review::where('status', 'spam')->count();

            return response()->json([
                'success' => true,
                'message' => 'Statistiques des avis récupérées',
                'data' => [
                    'total' => (int) $total,
                    'pending' => (int) $pending,
                    'approved' => (int) $approved,
                    'rejected' => (int) $rejected,
                    'spam' => (int) $spam,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les avis par statut.
     *
     * Endpoint : GET /api/v1/admin/reviews/stats/by-status
     */
    public function statsByStatus()
    {
        try {
            $reviews = Review::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Avis par statut récupérés',
                'data' => $reviews,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des avis par statut',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les avis par note.
     *
     * Endpoint : GET /api/v1/admin/reviews/stats/by-rating
     */
    public function statsByRating()
    {
        try {
            $reviews = Review::select('rating', DB::raw('COUNT(*) as count'))
                ->groupBy('rating')
                ->orderBy('rating', 'desc')
                ->pluck('count', 'rating')
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Avis par note récupérés',
                'data' => $reviews,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des avis par note',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer la note moyenne.
     *
     * Endpoint : GET /api/v1/admin/reviews/stats/average-rating
     */
    public function statsAverageRating()
    {
        try {
            $average = Review::avg('rating') ?? 0;

            return response()->json([
                'success' => true,
                'message' => 'Note moyenne récupérée',
                'data' => ['average' => round((float) $average, 1)],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la note moyenne',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approuver plusieurs avis en masse.
     *
     * Endpoint : POST /api/v1/admin/reviews/bulk-approve
     */
    public function bulkApprove(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun avis sélectionné',
                ], 422);
            }

            $count = Review::whereIn('id', $ids)->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "$count avis approuvés",
                'data' => ['count' => (int) $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'approbation en masse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rejeter plusieurs avis en masse.
     *
     * Endpoint : POST /api/v1/admin/reviews/bulk-reject
     */
    public function bulkReject(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun avis sélectionné',
                ], 422);
            }

            $count = Review::whereIn('id', $ids)->update([
                'status' => 'rejected',
            ]);

            return response()->json([
                'success' => true,
                'message' => "$count avis rejetés",
                'data' => ['count' => (int) $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet en masse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer plusieurs avis en masse.
     *
     * Endpoint : POST /api/v1/admin/reviews/bulk-delete
     */
    public function bulkDelete(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun avis sélectionné',
                ], 422);
            }

            $count = Review::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "$count avis supprimés",
                'data' => ['count' => (int) $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression en masse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un avis (soft delete).
     *
     * Endpoint : DELETE /api/v1/admin/reviews/{id}
     */
    public function destroy($id)
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avis introuvable',
                ], 404);
            }

            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'Avis supprimé avec succès',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'avis',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restaurer un avis supprimé.
     *
     * Endpoint : POST /api/v1/admin/reviews/{id}/restore
     */
    public function restore($id)
    {
        try {
            $review = Review::withTrashed()->find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avis introuvable',
                ], 404);
            }

            $review->restore();

            return response()->json([
                'success' => true,
                'message' => 'Avis restauré avec succès',
                'data' => $review,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration de l\'avis',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}