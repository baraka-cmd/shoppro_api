<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Seller\SellerReviewResource;
use App\Models\Review\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Controller : SellerReviewController
 *
 * Gère les avis sur les produits du vendeur connecté.
 *
 * Sécurité (défense en profondeur) :
 *   - Middleware : auth:sanctum + role:seller,admin + seller.active
 *   - Scope strict : review.seller_id === auth()->id()
 *   - Policies : ReviewPolicy appliquée sur chaque action
 *   - Aucune fuite d'avis d'autres vendeurs
 *
 * Actions disponibles :
 *   - index()    : Liste paginée des avis sur SES produits
 *   - show()     : Détail d'un avis (scopé)
 *   - respond()  : Répondre à un avis
 *   - approve()  : Approuver un avis en attente
 *   - reject()   : Rejeter un avis
 *   - markAsSpam(): Marquer un avis comme spam
 *
 * @package App\Http\Controllers\Api\Seller
 */
class SellerReviewController extends Controller
{
    // ============================================
    // INDEX — LISTE DES AVIS
    // ============================================

    /**
     * GET /api/v1/seller/reviews
     *
     * Liste paginée des avis sur les produits du vendeur.
     *
     * Query params :
     *   - status     : all | pending | approved | rejected | spam
     *   - rating     : 1-5
     *   - product_id : filtrer par produit
     *   - with_photos: true/false
     *   - verified   : true/false
     *   - search     : recherche dans le titre/contenu
     *   - sort_by    : created_at | rating | helpful_count
     *   - sort_dir   : asc | desc
     *   - per_page   : 15 par défaut, max 50
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);

            $query = Review::query()
                ->where('seller_id', $sellerId)
                ->with(['user', 'product', 'images']);

            // ============================================
            // FILTRE : Statut
            // ============================================
            $status = $request->input('status', 'all');
            if ($status !== 'all' && in_array($status, ['pending', 'approved', 'rejected', 'spam'], true)) {
                $query->where('status', $status);
            }

            // ============================================
            // FILTRE : Note
            // ============================================
            if ($request->filled('rating')) {
                $rating = (int) $request->input('rating');
                if ($rating >= 1 && $rating <= 5) {
                    $query->where('rating', $rating);
                }
            }

            // ============================================
            // FILTRE : Produit spécifique
            // ============================================
            if ($request->filled('product_id')) {
                $query->where('product_id', (int) $request->input('product_id'));
            }

            // ============================================
            // FILTRE : Avec photos
            // ============================================
            if ($request->boolean('with_photos')) {
                $query->whereHas('images');
            }

            // ============================================
            // FILTRE : Achat vérifié
            // ============================================
            if ($request->boolean('verified')) {
                $query->where('is_verified_purchase', true);
            }

            // ============================================
            // RECHERCHE (titre + contenu)
            // ============================================
            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                });
            }

            // ============================================
            // TRI
            // ============================================
            $sortBy = $request->input('sort_by', 'created_at');
            $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

            $allowedSorts = ['created_at', 'rating', 'helpful_count'];
            if (!in_array($sortBy, $allowedSorts, true)) {
                $sortBy = 'created_at';
            }

            $query->orderBy($sortBy, $sortDir);

            // ============================================
            // PAGINATION
            // ============================================
            $perPage = min((int) $request->input('per_page', 15), 50);
            $reviews = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Avis récupérés avec succès.',
                'data'    => SellerReviewResource::collection($reviews),
                'pagination' => [
                    'total'        => $reviews->total(),
                    'count'        => $reviews->count(),
                    'per_page'     => $reviews->perPage(),
                    'current_page' => $reviews->currentPage(),
                    'last_page'    => $reviews->lastPage(),
                    'has_more'     => $reviews->hasMorePages(),
                ],
                'stats' => $this->buildStats($sellerId),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerReviewController::index', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de récupérer les avis : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // SHOW — DÉTAIL
    // ============================================

    /**
     * GET /api/v1/seller/reviews/{review}
     */
    public function show(Request $request, Review $review): JsonResponse
    {
        try {
            // Vérifier la propriété via Policy
            if (!$request->user()->can('view', $review)) {
                return $this->error(
                    'Vous n\'êtes pas autorisé à voir cet avis.',
                    Response::HTTP_FORBIDDEN
                );
            }

            $review->load(['user', 'product', 'images']);

            return response()->json([
                'success' => true,
                'message' => 'Avis récupéré avec succès.',
                'data'    => new SellerReviewResource($review),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerReviewController::show', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de récupérer l\'avis : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // RESPOND — RÉPONDRE À UN AVIS
    // ============================================

    /**
     * POST /api/v1/seller/reviews/{review}/respond
     *
     * Permet au vendeur de répondre à un avis approuvé.
     * Body : { "response": "Merci pour votre avis..." }
     */
    public function respond(Request $request, Review $review): JsonResponse
    {
        try {
            // Vérifier la propriété via Policy
            if (!$request->user()->can('respond', $review)) {
                return $this->error(
                    'Vous n\'êtes pas autorisé à répondre à cet avis.',
                    Response::HTTP_FORBIDDEN
                );
            }

            // Validation
            $validated = $request->validate([
                'response' => 'required|string|min:3|max:2000',
            ], [
                'response.required' => 'La réponse est obligatoire.',
                'response.string'   => 'La réponse doit être un texte.',
                'response.min'      => 'La réponse doit contenir au moins :min caractères.',
                'response.max'      => 'La réponse ne peut pas dépasser :max caractères.',
            ]);

            DB::beginTransaction();

            $review->update([
                'admin_response'    => $validated['response'],
                'admin_response_at' => now(),
            ]);

            DB::commit();

            Log::info('SellerReview responded', [
                'review_id' => $review->id,
                'seller_id' => $review->seller_id,
            ]);

            // Recharger avec les relations
            $review->load(['user', 'product', 'images']);

            return response()->json([
                'success' => true,
                'message' => 'Réponse publiée avec succès.',
                'data'    => new SellerReviewResource($review->fresh(['user', 'product', 'images'])),
            ], Response::HTTP_OK);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Erreur de validation.', Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerReviewController::respond', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de publier la réponse : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // APPROVE — APPROUVER UN AVIS
    // ============================================

    /**
     * POST /api/v1/seller/reviews/{review}/approve
     *
     * Approuve un avis en attente.
     * L'avis devient visible publiquement sur le produit.
     */
    public function approve(Request $request, Review $review): JsonResponse
    {
        try {
            if (!$request->user()->can('approve', $review)) {
                return $this->error(
                    'Vous n\'êtes pas autorisé à approuver cet avis.',
                    Response::HTTP_FORBIDDEN
                );
            }

            if ($review->status === 'approved') {
                return $this->error(
                    'Cet avis est déjà approuvé.',
                    Response::HTTP_CONFLICT
                );
            }

            DB::beginTransaction();

            $review->update([
                'status'      => 'approved',
                'approved_at' => now(),
            ]);

            // Mettre à jour les stats du produit (note moyenne, total)
            $this->updateProductStats($review->product_id);

            DB::commit();

            Log::info('SellerReview approved', [
                'review_id' => $review->id,
                'seller_id' => $review->seller_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Avis approuvé avec succès.',
                'data'    => new SellerReviewResource($review->fresh(['user', 'product', 'images'])),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerReviewController::approve', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible d\'approuver l\'avis : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // REJECT — REJETER UN AVIS
    // ============================================

    /**
     * POST /api/v1/seller/reviews/{review}/reject
     *
     * Rejette un avis avec une raison optionnelle.
     * Body : { "reason": "Contenu inapproprié" }
     */
    public function reject(Request $request, Review $review): JsonResponse
    {
        try {
            if (!$request->user()->can('reject', $review)) {
                return $this->error(
                    'Vous n\'êtes pas autorisé à rejeter cet avis.',
                    Response::HTTP_FORBIDDEN
                );
            }

            if ($review->status === 'rejected') {
                return $this->error(
                    'Cet avis est déjà rejeté.',
                    Response::HTTP_CONFLICT
                );
            }

            $validated = $request->validate([
                'reason' => 'nullable|string|max:500',
            ]);

            DB::beginTransaction();

            $review->update([
                'status' => 'rejected',
            ]);

            // Mettre à jour les stats du produit (car un avis rejeté n'est plus compté)
            if ($review->wasChanged('status') && $review->getOriginal('status') === 'approved') {
                $this->updateProductStats($review->product_id);
            }

            DB::commit();

            Log::info('SellerReview rejected', [
                'review_id' => $review->id,
                'seller_id' => $review->seller_id,
                'reason'    => $validated['reason'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Avis rejeté.',
                'data'    => new SellerReviewResource($review->fresh(['user', 'product', 'images'])),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerReviewController::reject', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de rejeter l\'avis : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // MARK AS SPAM
    // ============================================

    /**
     * POST /api/v1/seller/reviews/{review}/spam
     *
     * Marque un avis comme spam.
     */
    public function markAsSpam(Request $request, Review $review): JsonResponse
    {
        try {
            if (!$request->user()->can('markAsSpam', $review)) {
                return $this->error(
                    'Vous n\'êtes pas autorisé à marquer cet avis comme spam.',
                    Response::HTTP_FORBIDDEN
                );
            }

            if ($review->status === 'spam') {
                return $this->error(
                    'Cet avis est déjà marqué comme spam.',
                    Response::HTTP_CONFLICT
                );
            }

            DB::beginTransaction();

            $wasApproved = $review->status === 'approved';

            $review->update([
                'status' => 'spam',
            ]);

            if ($wasApproved) {
                $this->updateProductStats($review->product_id);
            }

            DB::commit();

            Log::info('SellerReview marked as spam', [
                'review_id' => $review->id,
                'seller_id' => $review->seller_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Avis marqué comme spam.',
                'data'    => new SellerReviewResource($review->fresh(['user', 'product', 'images'])),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerReviewController::markAsSpam', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de marquer l\'avis comme spam : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // HELPERS PRIVÉS
    // ============================================

    /**
     * Résout l'ID du vendeur cible.
     * Admin peut passer ?seller_id=X pour cibler un vendeur spécifique.
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
     * Calcule les statistiques globales des avis du vendeur.
     */
    private function buildStats(int $sellerId): array
    {
        $baseQuery = Review::where('seller_id', $sellerId);

        $total = (clone $baseQuery)->count();
        $pending = (clone $baseQuery)->where('status', 'pending')->count();
        $approved = (clone $baseQuery)->where('status', 'approved')->count();
        $rejected = (clone $baseQuery)->where('status', 'rejected')->count();
        $spam = (clone $baseQuery)->where('status', 'spam')->count();
        $withPhotos = (clone $baseQuery)->whereHas('images')->count();
        $verified = (clone $baseQuery)->where('is_verified_purchase', true)->count();

        $averageRating = (clone $baseQuery)
            ->where('status', 'approved')
            ->avg('rating') ?? 0;

        return [
            'total'          => $total,
            'pending'        => $pending,
            'approved'       => $approved,
            'rejected'       => $rejected,
            'spam'           => $spam,
            'with_photos'    => $withPhotos,
            'verified'       => $verified,
            'average_rating' => round((float) $averageRating, 2),
            'to_moderate'    => $pending,
        ];
    }

    /**
     * Recalcule les statistiques du produit (note moyenne, total avis).
     */
    private function updateProductStats(int $productId): void
    {
        $product = \App\Models\Catalog\Product::find($productId);

        if (!$product) {
            return;
        }

        $approvedReviews = Review::where('product_id', $productId)
            ->where('status', 'approved')
            ->get();

        $count = $approvedReviews->count();
        $average = $count > 0 ? $approvedReviews->avg('rating') : 0;

        $product->update([
            'reviews_count'  => $count,
            'average_rating' => round((float) $average, 2),
        ]);
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