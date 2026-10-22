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
    // ============================================
    // LISTE DES AVIS
    // ============================================

    /**
     * Récupérer la liste paginée des avis.
     *
     * Endpoint : GET /api/v1/admin/reviews
     *
     * Params :
     * - per_page : nombre par page (défaut: 20)
     * - page : page (défaut: 1)
     * - status : pending | approved | rejected | spam
     * - rating : 1-5
     * - product_id : filtrer par produit
     * - user_id : filtrer par utilisateur
     * - search : recherche dans titre/contenu/user
     * - with_photos : 1 pour n'avoir que les avis avec photos
     * - sort_by : created_at | rating | helpful_count
     * - sort_direction : asc | desc
     * - group_by : product (regroupe par produit)
     */
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->query('per_page', 20);
            $perPage = min(max($perPage, 1), 100);

            $query = Review::with(['user.profile', 'product', 'images'])
                ->orderByDesc('created_at');

            // ============================================
            // FILTRES
            // ============================================
            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }

            if ($request->filled('rating')) {
                $query->where('rating', (int) $request->query('rating'));
            }

            if ($request->filled('product_id')) {
                $query->where('product_id', $request->query('product_id'));
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->query('user_id'));
            }

            // ✅ Filtre : avis avec photos uniquement
            if ($request->filled('with_photos') && $request->query('with_photos') == '1') {
                $query->whereHas('images');
            }

            // ✅ Filtre : avis vérifiés uniquement
            if ($request->filled('verified') && $request->query('verified') == '1') {
                $query->where('is_verified_purchase', true);
            }

            // Recherche
            if ($request->filled('search')) {
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
            $allowedSort = ['created_at', 'rating', 'helpful_count', 'updated_at'];
            if (in_array($sortBy, $allowedSort)) {
                $query->orderBy($sortBy, $sortDirection);
            }

            // ============================================
            // MODE GROUPÉ PAR PRODUIT
            // ============================================
            if ($request->query('group_by') === 'product') {
                return $this->groupedByProduct($query, $perPage);
            }

            // ============================================
            // MODE NORMAL (liste plate)
            // ============================================
            $reviews = $query->paginate($perPage);

            $data = $reviews->map(function ($review) {
                return $this->formatReview($review);
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

    // ============================================
    // GROUPEMENT PAR PRODUIT
    // ============================================

    /**
     * Groupe les avis par produit.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $perPage
     * @return \Illuminate\Http\JsonResponse
     */
    private function groupedByProduct($query, int $perPage)
    {
        // Récupérer tous les avis correspondants
        $reviews = $query->get();

        // Grouper par product_id
        $grouped = $reviews->groupBy('product_id');

        // Construire la réponse
        $products = [];
        foreach ($grouped as $productId => $productReviews) {
            $product = $productReviews->first()->product;

            if (!$product) continue;

            // Statistiques du groupe
            $ratings = $productReviews->pluck('rating');
            $avgRating = $ratings->avg();
            $withPhotos = $productReviews->filter(function ($r) {
                return $r->images->isNotEmpty();
            })->count();
            $pending = $productReviews->where('status', 'pending')->count();
            $approved = $productReviews->where('status', 'approved')->count();
            $rejected = $productReviews->where('status', 'rejected')->count();
            $spam = $productReviews->where('status', 'spam')->count();

            $products[] = [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->main_image
                        ? asset('storage/' . $product->main_image)
                        : null,
                ],
                'stats' => [
                    'total' => $productReviews->count(),
                    'average_rating' => round($avgRating, 1),
                    'with_photos' => $withPhotos,
                    'pending' => $pending,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'spam' => $spam,
                ],
                'reviews' => $productReviews->map(function ($review) {
                    return $this->formatReview($review);
                })->values()->toArray(),
            ];
        }

        // Trier par nombre d'avis (décroissant)
        usort($products, function ($a, $b) {
            return $b['stats']['total'] <=> $a['stats']['total'];
        });

        return response()->json([
            'success' => true,
            'message' => 'Avis groupés par produit',
            'data' => $products,
            'total_products' => count($products),
        ], 200);
    }

    // ============================================
    // FORMATAGE D'UN AVIS
    // ============================================

    /**
     * Formate un avis pour l'API.
     *
     * @param Review $review
     * @return array
     */
    private function formatReview(Review $review): array
    {
        return [
            'id' => $review->id,
            'title' => $review->title,
            'content' => $review->content,
            'rating' => (int) $review->rating,
            'quality_rating' => $review->quality_rating ? (int) $review->quality_rating : null,
            'price_rating' => $review->price_rating ? (int) $review->price_rating : null,
            'shipping_rating' => $review->shipping_rating ? (int) $review->shipping_rating : null,
            'status' => $review->status,
            'status_label' => $review->status_label,
            'is_verified_purchase' => (bool) $review->is_verified_purchase,
            'is_recommended' => (bool) $review->is_recommended,
            'helpful_count' => (int) $review->helpful_count,
            'not_helpful_count' => (int) $review->not_helpful_count,
            'views_count' => (int) $review->views_count,
            'admin_response' => $review->admin_response,
            'admin_response_at' => $review->admin_response_at?->toIso8601String(),
            'approved_at' => $review->approved_at?->toIso8601String(),
            'created_at' => $review->created_at?->toIso8601String(),
            'updated_at' => $review->updated_at?->toIso8601String(),

            // ✅ Utilisateur
            'user' => $review->user ? [
                'id' => $review->user->id,
                'name' => $review->user->name,
                'email' => $review->user->email,
                'avatar' => $review->user->profile?->avatar
                    ? asset('storage/' . $review->user->profile->avatar)
                    : null,
            ] : null,

            // ✅ Produit
            'product' => $review->product ? [
                'id' => $review->product->id,
                'name' => $review->product->name,
                'sku' => $review->product->sku,
                'image' => $review->product->main_image
                    ? asset('storage/' . $review->product->main_image)
                    : null,
            ] : null,

            // ✅ Images (avec URLs complètes)
            'images' => $review->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'url' => $image->image_path
                        ? asset('storage/' . $image->image_path)
                        : null,
                    'thumbnail' => $image->thumbnail_path
                        ? asset('storage/' . $image->thumbnail_path)
                        : null,
                    'position' => $image->position,
                    'type' => $image->type,
                ];
            })->values()->toArray(),
        ];
    }

    // ============================================
    // DÉTAILS
    // ============================================

    public function show($id)
    {
        try {
            $review = Review::with(['user.profile', 'product', 'images', 'votes.user'])
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
                'data' => $this->formatReview($review),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'avis',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // STATS PAR PRODUIT (NOUVEAU)
    // ============================================

    /**
     * Récupère les statistiques des avis groupées par produit.
     *
     * Endpoint : GET /api/v1/admin/reviews/stats/by-product
     */
    public function statsByProduct()
    {
        try {
            $stats = Review::select(
                    'product_id',
                    DB::raw('COUNT(*) as total'),
                    DB::raw('AVG(rating) as average_rating'),
                    DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending'),
                    DB::raw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved'),
                    DB::raw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected'),
                    DB::raw('SUM(CASE WHEN status = "spam" THEN 1 ELSE 0 END) as spam')
                )
                ->groupBy('product_id')
                ->with('product')
                ->get();

            $data = $stats->map(function ($stat) {
                return [
                    'product_id' => $stat->product_id,
                    'product_name' => $stat->product?->name,
                    'product_image' => $stat->product?->main_image
                        ? asset('storage/' . $stat->product->main_image)
                        : null,
                    'total' => (int) $stat->total,
                    'average_rating' => round((float) $stat->average_rating, 1),
                    'pending' => (int) $stat->pending,
                    'approved' => (int) $stat->approved,
                    'rejected' => (int) $stat->rejected,
                    'spam' => (int) $stat->spam,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Statistiques par produit récupérées',
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques par produit',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // STATS GLOBALES
    // ============================================

    public function statsCount()
    {
        try {
            $total = Review::count();
            $pending = Review::where('status', 'pending')->count();
            $approved = Review::where('status', 'approved')->count();
            $rejected = Review::where('status', 'rejected')->count();
            $spam = Review::where('status', 'spam')->count();
            $withPhotos = Review::whereHas('images')->count();
            $average = Review::avg('rating') ?? 0;

            return response()->json([
                'success' => true,
                'message' => 'Statistiques des avis récupérées',
                'data' => [
                    'total' => (int) $total,
                    'pending' => (int) $pending,
                    'approved' => (int) $approved,
                    'rejected' => (int) $rejected,
                    'spam' => (int) $spam,
                    'with_photos' => (int) $withPhotos,
                    'average_rating' => round((float) $average, 1),
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

    // ============================================
    // AVIS PAR STATUT
    // ============================================

    public function pending(Request $request)
    {
        return $this->filterByStatus($request, 'pending');
    }

    public function approved(Request $request)
    {
        return $this->filterByStatus($request, 'approved');
    }

    public function rejected(Request $request)
    {
        return $this->filterByStatus($request, 'rejected');
    }

    public function spam(Request $request)
    {
        return $this->filterByStatus($request, 'spam');
    }

    private function filterByStatus(Request $request, string $status)
    {
        try {
            $perPage = (int) $request->query('per_page', 20);
            $perPage = min(max($perPage, 1), 100);

            $reviews = Review::with(['user.profile', 'product', 'images'])
                ->where('status', $status)
                ->orderByDesc('created_at')
                ->paginate($perPage);

            $data = $reviews->map(function ($review) {
                return $this->formatReview($review);
            });

            return response()->json([
                'success' => true,
                'message' => "Avis '{$status}' récupérés",
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
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // AVIS PAR PRODUIT / USER
    // ============================================

    public function byProduct(Request $request, $productId)
    {
        try {
            $perPage = (int) $request->query('per_page', 20);
            $perPage = min(max($perPage, 1), 100);

            $reviews = Review::with(['user.profile', 'product', 'images'])
                ->where('product_id', $productId)
                ->orderByDesc('created_at')
                ->paginate($perPage);

            $data = $reviews->map(function ($review) {
                return $this->formatReview($review);
            });

            return response()->json([
                'success' => true,
                'message' => 'Avis du produit récupérés',
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
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function byUser($userId)
    {
        try {
            $reviews = Review::with(['product', 'images'])
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->get();

            $data = $reviews->map(function ($review) {
                return $this->formatReview($review);
            });

            return response()->json([
                'success' => true,
                'message' => 'Avis de l\'utilisateur récupérés',
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // MODÉRATION
    // ============================================

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

            // Mettre à jour les stats du produit
            if ($review->product) {
                $this->updateProductStats($review->product_id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Avis approuvé avec succès',
                'data' => $this->formatReview($review->fresh(['user.profile', 'product', 'images'])),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'approbation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
                'data' => $this->formatReview($review->fresh(['user.profile', 'product', 'images'])),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
                'data' => $this->formatReview($review->fresh(['user.profile', 'product', 'images'])),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
                'data' => $this->formatReview($review->fresh(['user.profile', 'product', 'images'])),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // RÉPONSES ADMIN
    // ============================================

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
                'data' => $this->formatReview($review->fresh(['user.profile', 'product', 'images'])),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateResponse(Request $request, $id)
    {
        return $this->respond($request, $id);
    }

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
                'data' => $this->formatReview($review->fresh(['user.profile', 'product', 'images'])),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // RECOMMANDATION
    // ============================================

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
                'data' => $this->formatReview($review->fresh(['user.profile', 'product', 'images'])),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
                'data' => $this->formatReview($review->fresh(['user.profile', 'product', 'images'])),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // BULK ACTIONS
    // ============================================

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
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // SUPPRESSION / RESTAURATION
    // ============================================

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

            $productId = $review->product_id;
            $review->delete();

            // Mettre à jour les stats
            if ($productId) {
                $this->updateProductStats($productId);
            }

            return response()->json([
                'success' => true,
                'message' => 'Avis supprimé avec succès',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
                'data' => $this->formatReview($review->fresh(['user.profile', 'product', 'images'])),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // HELPER PRIVÉ
    // ============================================

    /**
     * Met à jour les statistiques d'un produit.
     */
    private function updateProductStats(int $productId): void
    {
        $stats = Review::where('product_id', $productId)
            ->where('status', 'approved')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        Product::where('id', $productId)->update([
            'average_rating' => round($stats->avg_rating ?? 0, 2),
            'reviews_count' => $stats->total ?? 0,
        ]);
    }
}