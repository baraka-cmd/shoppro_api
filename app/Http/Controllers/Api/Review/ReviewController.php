<?php

namespace App\Http\Controllers\Api\Review;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Review\StoreReviewRequest;
use App\Http\Resources\Api\Review\ReviewResource;
use App\Models\Catalog\Product;
use App\Models\Review\Review;
use App\Models\Review\ReviewImage;
use App\Models\Review\ReviewVote;
use App\Models\Order\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    // ============================================
    // LISTE DES AVIS D'UN PRODUIT (PUBLIC)
    // ============================================
    public function index(Request $request, $productId)
    {
        try {
            $perPage = (int) $request->query('per_page', 10);
            $perPage = min(max($perPage, 1), 50);

            $product = Product::find($productId);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produit introuvable.',
                ], 404);
            }

            $reviews = Review::with(['user.profile', 'images'])
                ->where('product_id', $productId)
                ->where('status', 'approved')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Liste des avis.',
                'data' => ReviewResource::collection($reviews->items()),
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
                'message' => 'Erreur lors de la récupération des avis.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // CRÉER UN AVIS (AUTHENTIFIÉ)
    // ============================================
    public function store(StoreReviewRequest $request)
    {
        try {
            $user = Auth::user();
            $productId = (int) $request->input('product_id');

            // Vérifier si l'utilisateur a déjà laissé un avis
            $existing = Review::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous avez déjà laissé un avis pour ce produit.',
                ], 422);
            }

            // Vérifier si l'utilisateur a acheté ce produit
            $hasPurchased = Order::where('user_id', $user->id)
                ->whereHas('items', function ($q) use ($productId) {
                    $q->where('product_id', $productId);
                })
                ->whereIn('status', ['delivered', 'shipped', 'processing', 'confirmed'])
                ->exists();

            DB::beginTransaction();

            // Créer l'avis
            $review = Review::create([
                'user_id' => $user->id,
                'product_id' => $productId,
                'rating' => (int) $request->input('rating'),
                'quality_rating' => $request->filled('quality_rating')
                    ? (int) $request->input('quality_rating')
                    : null,
                'price_rating' => $request->filled('price_rating')
                    ? (int) $request->input('price_rating')
                    : null,
                'shipping_rating' => $request->filled('shipping_rating')
                    ? (int) $request->input('shipping_rating')
                    : null,
                'title' => $request->input('title'),
                'content' => $request->input('comment'),
                'is_verified_purchase' => $hasPurchased,
                'is_recommended' => true,
                'status' => 'approved', // ⚠️ Change en 'pending' si modération requise
            ]);

            // Gérer les images
            if ($request->hasFile('images')) {
                $images = $request->file('images');

                // Sécurité : forcer un tableau
                if (!is_array($images)) {
                    $images = [$images];
                }

                foreach ($images as $index => $image) {
                    if (!$image->isValid()) continue;

                    $path = $image->store('reviews', 'public');

                    ReviewImage::create([
                        'review_id' => $review->id,
                        'image_path' => $path,
                        'thumbnail_path' => $path,
                        'alt_text' => 'Photo avis ' . ($index + 1),
                        'position' => $index,
                        'is_active' => true,
                    ]);
                }
            }

            DB::commit();

            // Mettre à jour les stats du produit
            $this->updateProductStats($productId);

            // Recharger avec les relations
            $review->load(['user.profile', 'images']);

            return response()->json([
                'success' => true,
                'message' => 'Merci ! Votre avis a été publié.',
                'data' => new ReviewResource($review),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'avis.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // MODIFIER UN AVIS
    // ============================================
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $review = Review::where('user_id', $user->id)->find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avis introuvable ou non autorisé.',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'rating' => 'nullable|integer|min:1|max:5',
                'comment' => 'nullable|string|min:5|max:500',
                'title' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $review->update([
                'rating' => $request->input('rating', $review->rating),
                'content' => $request->input('comment', $review->content),
                'title' => $request->input('title', $review->title),
            ]);

            $this->updateProductStats($review->product_id);

            $review->load(['user.profile', 'images']);

            return response()->json([
                'success' => true,
                'message' => 'Avis mis à jour.',
                'data' => new ReviewResource($review),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // SUPPRIMER UN AVIS
    // ============================================
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $review = Review::where('user_id', $user->id)->find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avis introuvable ou non autorisé.',
                ], 404);
            }

            // Supprimer les images du storage
            foreach ($review->images as $image) {
                if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
                    Storage::disk('public')->delete($image->image_path);
                }
            }

            $productId = $review->product_id;
            $review->delete();

            $this->updateProductStats($productId);

            return response()->json([
                'success' => true,
                'message' => 'Avis supprimé.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // VOTER POUR UN AVIS (UTILE / PAS UTILE)
    // ============================================
    public function vote(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'vote_type' => 'required|in:helpful,not_helpful',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de vote invalide.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user();
            $review = Review::find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avis introuvable.',
                ], 404);
            }

            // Un utilisateur ne peut pas voter pour son propre avis
            if ($review->user_id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas voter pour votre propre avis.',
                ], 422);
            }

            $voteType = $request->input('vote_type');

            // Vérifier si l'utilisateur a déjà voté
            $existingVote = ReviewVote::where('review_id', $id)
                ->where('user_id', $user->id)
                ->first();

            DB::beginTransaction();

            if ($existingVote) {
                // Si même vote → on retire le vote (toggle)
                if ($existingVote->vote_type === $voteType) {
                    $existingVote->delete();
                    $this->decrementVoteCount($review, $voteType);
                    $action = 'removed';
                } else {
                    // Changement de vote
                    $this->decrementVoteCount($review, $existingVote->vote_type);
                    $existingVote->update(['vote_type' => $voteType]);
                    $this->incrementVoteCount($review, $voteType);
                    $action = 'updated';
                }
            } else {
                // Nouveau vote
                ReviewVote::create([
                    'review_id' => $id,
                    'user_id' => $user->id,
                    'vote_type' => $voteType,
                    'voted_at' => now(),
                ]);
                $this->incrementVoteCount($review, $voteType);
                $action = 'created';
            }

            DB::commit();

            $review->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Vote enregistré.',
                'data' => [
                    'action' => $action,
                    'helpful_count' => $review->helpful_count,
                    'not_helpful_count' => $review->not_helpful_count,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du vote.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // HELPERS PRIVÉS
    // ============================================

    private function incrementVoteCount(Review $review, string $voteType): void
    {
        if ($voteType === 'helpful') {
            $review->increment('helpful_count');
        } else {
            $review->increment('not_helpful_count');
        }
    }

    private function decrementVoteCount(Review $review, string $voteType): void
    {
        if ($voteType === 'helpful' && $review->helpful_count > 0) {
            $review->decrement('helpful_count');
        } elseif ($voteType === 'not_helpful' && $review->not_helpful_count > 0) {
            $review->decrement('not_helpful_count');
        }
    }

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