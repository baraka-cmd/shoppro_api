<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\Promotion;
use App\Models\Marketing\FlashSale;
use App\Models\Marketing\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AdminPromotionController extends Controller
{
    /**
     * Récupérer la liste paginée des promotions.
     *
     * Endpoint : GET /api/v1/admin/promotions
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $page = $request->query('page', 1);

            $query = Promotion::orderByDesc('created_at');

            // Filtres
            if ($request->has('type') && !empty($request->query('type'))) {
                $query->where('type', $request->query('type'));
            }

            if ($request->has('status') && !empty($request->query('status'))) {
                $status = $request->query('status');
                if ($status == 'active') {
                    $query->where('is_active', true)
                          ->whereDate('start_date', '<=', now())
                          ->whereDate('end_date', '>=', now());
                } elseif ($status == 'upcoming') {
                    $query->where('is_active', true)
                          ->whereDate('start_date', '>', now());
                } elseif ($status == 'expired') {
                    $query->whereDate('end_date', '<', now());
                } elseif ($status == 'paused') {
                    $query->where('is_active', false);
                }
            }

            if ($request->has('search') && !empty($request->query('search'))) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->query('sort_by', 'created_at');
            $sortDirection = $request->query('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $promotions = $query->paginate($perPage, ['*'], 'page', $page);

            $data = $promotions->map(function ($promotion) {
                return $this->formatPromotion($promotion);
            });

            return response()->json([
                'success' => true,
                'message' => 'Promotions récupérées avec succès',
                'data' => $data,
                'pagination' => [
                    'total' => $promotions->total(),
                    'count' => $promotions->count(),
                    'per_page' => $promotions->perPage(),
                    'current_page' => $promotions->currentPage(),
                    'last_page' => $promotions->lastPage(),
                    'has_more' => $promotions->hasMorePages(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des promotions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les détails d'une promotion.
     *
     * Endpoint : GET /api/v1/admin/promotions/{id}
     */
    public function show($id)
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion introuvable',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Promotion récupérée avec succès',
                'data' => $this->formatPromotion($promotion),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la promotion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer une promotion par son slug.
     *
     * Endpoint : GET /api/v1/admin/promotions/by-slug/{slug}
     */
    public function showBySlug($slug)
    {
        try {
            $promotion = Promotion::where('slug', $slug)->first();

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion introuvable',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Promotion récupérée avec succès',
                'data' => $this->formatPromotion($promotion),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la promotion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les promotions actives.
     *
     * Endpoint : GET /api/v1/admin/promotions/active
     */
    public function active(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);

            $promotions = Promotion::where('is_active', true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Promotions actives récupérées',
                'data' => $promotions->items(),
                'pagination' => [
                    'total' => $promotions->total(),
                    'count' => $promotions->count(),
                    'per_page' => $promotions->perPage(),
                    'current_page' => $promotions->currentPage(),
                    'last_page' => $promotions->lastPage(),
                    'has_more' => $promotions->hasMorePages(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des promotions actives',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les promotions à venir.
     *
     * Endpoint : GET /api/v1/admin/promotions/upcoming
     */
    public function upcoming(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);

            $promotions = Promotion::where('is_active', true)
                ->whereDate('start_date', '>', now())
                ->orderBy('start_date', 'asc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Promotions à venir récupérées',
                'data' => $promotions->items(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des promotions à venir',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les promotions expirées.
     *
     * Endpoint : GET /api/v1/admin/promotions/expired
     */
    public function expired(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);

            $promotions = Promotion::whereDate('end_date', '<', now())
                ->orderByDesc('end_date')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Promotions expirées récupérées',
                'data' => $promotions->items(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des promotions expirées',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les promotions en vedette.
     *
     * Endpoint : GET /api/v1/admin/promotions/featured
     */
    public function featured(Request $request)
    {
        try {
            $limit = $request->query('limit', 10);

            $promotions = Promotion::where('is_featured', true)
                ->where('is_active', true)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Promotions en vedette récupérées',
                'data' => $promotions->map(function ($p) {
                    return $this->formatPromotion($p);
                }),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des promotions en vedette',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les promotions expirant bientôt.
     *
     * Endpoint : GET /api/v1/admin/promotions/expiring-soon
     */
    public function expiringSoon()
    {
        try {
            $promotions = Promotion::where('is_active', true)
                ->whereDate('end_date', '>=', now())
                ->whereDate('end_date', '<=', now()->addDays(3))
                ->orderBy('end_date', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Promotions expirant bientôt récupérées',
                'data' => $promotions->map(function ($p) {
                    return $this->formatPromotion($p);
                }),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des promotions expirant bientôt',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer une nouvelle promotion.
     *
     * Endpoint : POST /api/v1/admin/promotions
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:150',
                'description' => 'nullable|string',
                'type' => 'required|string|in:discount,bogo,bundle,flash_sale,clearance',
                'discount_type' => 'required|string|in:percentage,fixed',
                'discount_value' => 'required|numeric|min:0',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'minimum_order' => 'nullable|numeric|min:0',
                'usage_limit' => 'nullable|integer|min:1',
                'usage_per_user' => 'nullable|integer|min:1',
                'banner' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'is_featured' => 'nullable|boolean',
                'show_countdown' => 'nullable|boolean',
            ]);

            $slug = Str::slug($validated['name']) . '-' . Str::random(6);

            $promotion = Promotion::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'minimum_order' => $validated['minimum_order'] ?? 0,
                'usage_limit' => $validated['usage_limit'] ?? null,
                'usage_per_user' => $validated['usage_per_user'] ?? null,
                'usage_count' => 0,
                'banner' => $validated['banner'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'is_featured' => $validated['is_featured'] ?? false,
                'show_countdown' => $validated['show_countdown'] ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Promotion créée avec succès',
                'data' => $this->formatPromotion($promotion),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la promotion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour une promotion.
     *
     * Endpoint : PUT /api/v1/admin/promotions/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion introuvable',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:150',
                'description' => 'nullable|string',
                'type' => 'sometimes|string|in:discount,bogo,bundle,flash_sale,clearance',
                'discount_type' => 'sometimes|string|in:percentage,fixed',
                'discount_value' => 'sometimes|numeric|min:0',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date|after:start_date',
                'minimum_order' => 'nullable|numeric|min:0',
                'usage_limit' => 'nullable|integer|min:1',
                'usage_per_user' => 'nullable|integer|min:1',
                'banner' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'is_featured' => 'nullable|boolean',
                'show_countdown' => 'nullable|boolean',
            ]);

            $promotion->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Promotion mise à jour avec succès',
                'data' => $this->formatPromotion($promotion),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la promotion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une promotion (soft delete).
     *
     * Endpoint : DELETE /api/v1/admin/promotions/{id}
     */
    public function destroy($id)
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion introuvable',
                ], 404);
            }

            $promotion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Promotion supprimée avec succès',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la promotion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restaurer une promotion supprimée.
     *
     * Endpoint : POST /api/v1/admin/promotions/{id}/restore
     */
    public function restore($id)
    {
        try {
            $promotion = Promotion::withTrashed()->find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion introuvable',
                ], 404);
            }

            $promotion->restore();

            return response()->json([
                'success' => true,
                'message' => 'Promotion restaurée avec succès',
                'data' => $this->formatPromotion($promotion),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration de la promotion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activer une promotion.
     *
     * Endpoint : POST /api/v1/admin/promotions/{id}/activate
     */
    public function activate($id)
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion introuvable',
                ], 404);
            }

            $promotion->is_active = true;
            $promotion->save();

            return response()->json([
                'success' => true,
                'message' => 'Promotion activée avec succès',
                'data' => $this->formatPromotion($promotion),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'activation de la promotion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Désactiver une promotion.
     *
     * Endpoint : POST /api/v1/admin/promotions/{id}/deactivate
     */
    public function deactivate($id)
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion introuvable',
                ], 404);
            }

            $promotion->is_active = false;
            $promotion->save();

            return response()->json([
                'success' => true,
                'message' => 'Promotion désactivée avec succès',
                'data' => $this->formatPromotion($promotion),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désactivation de la promotion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre en pause une promotion.
     *
     * Endpoint : POST /api/v1/admin/promotions/{id}/pause
     */
    public function pause($id)
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion introuvable',
                ], 404);
            }

            $promotion->is_active = false;
            $promotion->save();

            return response()->json([
                'success' => true,
                'message' => 'Promotion mise en pause',
                'data' => $this->formatPromotion($promotion),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise en pause',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reprendre une promotion en pause.
     *
     * Endpoint : POST /api/v1/admin/promotions/{id}/resume
     */
    public function resume($id)
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion introuvable',
                ], 404);
            }

            $promotion->is_active = true;
            $promotion->save();

            return response()->json([
                'success' => true,
                'message' => 'Promotion reprise',
                'data' => $this->formatPromotion($promotion),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la reprise',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Annuler une promotion.
     *
     * Endpoint : POST /api/v1/admin/promotions/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion introuvable',
                ], 404);
            }

            $promotion->is_active = false;
            $promotion->end_date = now();
            $promotion->save();

            return response()->json([
                'success' => true,
                'message' => 'Promotion annulée',
                'data' => $this->formatPromotion($promotion),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre en vedette une promotion.
     *
     * Endpoint : POST /api/v1/admin/promotions/{id}/feature
     */
    public function toggleFeatured(Request $request, $id)
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion introuvable',
                ], 404);
            }

            $promotion->is_featured = $request->input('is_featured', !$promotion->is_featured);
            $promotion->save();

            return response()->json([
                'success' => true,
                'message' => 'Vedette mise à jour',
                'data' => $this->formatPromotion($promotion),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise en vedette',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vérifier la disponibilité d'un slug.
     *
     * Endpoint : GET /api/v1/admin/promotions/check-slug/{slug}
     */
    public function checkSlug($slug)
    {
        try {
            $exists = Promotion::where('slug', $slug)->exists();

            return response()->json([
                'success' => true,
                'message' => 'Vérification du slug',
                'data' => ['available' => !$exists],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du slug',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les statistiques des promotions.
     *
     * Endpoint : GET /api/v1/admin/promotions/stats/count
     */
    public function statsCount()
    {
        try {
            $total = Promotion::count();
            $active = Promotion::where('is_active', true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->count();
            $upcoming = Promotion::where('is_active', true)
                ->whereDate('start_date', '>', now())
                ->count();
            $expired = Promotion::whereDate('end_date', '<', now())->count();
            $paused = Promotion::where('is_active', false)->count();

            return response()->json([
                'success' => true,
                'message' => 'Statistiques récupérées',
                'data' => [
                    'total' => (int) $total,
                    'active' => (int) $active,
                    'upcoming' => (int) $upcoming,
                    'expired' => (int) $expired,
                    'paused' => (int) $paused,
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
     * Récupérer les promotions par statut.
     *
     * Endpoint : GET /api/v1/admin/promotions/stats/by-status
     */
    public function statsByStatus()
    {
        try {
            $stats = [
                'active' => Promotion::where('is_active', true)
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->count(),
                'upcoming' => Promotion::where('is_active', true)
                    ->whereDate('start_date', '>', now())
                    ->count(),
                'expired' => Promotion::whereDate('end_date', '<', now())->count(),
                'paused' => Promotion::where('is_active', false)->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Promotions par statut récupérées',
                'data' => $stats,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des promotions par statut',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les statistiques détaillées d'une promotion.
     *
     * Endpoint : GET /api/v1/admin/promotions/{id}/stats
     */
    public function promotionStats($id)
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion introuvable',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Statistiques de la promotion récupérées',
                'data' => [
                    'usage_count' => (int) $promotion->usage_count,
                    'usage_limit' => $promotion->usage_limit ? (int) $promotion->usage_limit : null,
                    'total_revenue' => (float) ($promotion->total_revenue ?? 0),
                    'orders_count' => (int) ($promotion->orders_count ?? 0),
                    'total_savings' => (float) ($promotion->total_savings ?? 0),
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
     * Formater une promotion pour la réponse API.
     */
    private function formatPromotion(Promotion $promotion): array
    {
        $now = now();
        $isCurrentlyActive = $promotion->is_active
            && $promotion->start_date <= $now
            && $promotion->end_date >= $now;

        return [
            'id' => $promotion->id,
            'name' => $promotion->name,
            'slug' => $promotion->slug,
            'description' => $promotion->description,
            'type' => $promotion->type,
            'discount_type' => $promotion->discount_type,
            'discount_value' => (float) $promotion->discount_value,
            'start_date' => $promotion->start_date->toIso8601String(),
            'end_date' => $promotion->end_date->toIso8601String(),
            'minimum_order' => (float) $promotion->minimum_order,
            'usage_limit' => $promotion->usage_limit ? (int) $promotion->usage_limit : null,
            'usage_count' => (int) $promotion->usage_count,
            'usage_per_user' => $promotion->usage_per_user ? (int) $promotion->usage_per_user : null,
            'banner' => $promotion->banner,
            'is_active' => (bool) $promotion->is_active,
            'is_featured' => (bool) $promotion->is_featured,
            'show_countdown' => (bool) $promotion->show_countdown,
            'is_currently_active' => $isCurrentlyActive,
            'is_upcoming' => $promotion->is_active && $promotion->start_date > $now,
            'is_expired' => $promotion->end_date < $now,
            'is_paused' => !$promotion->is_active,
            'days_remaining' => $isCurrentlyActive ? (int) $now->diffInDays($promotion->end_date) : 0,
            'created_at' => $promotion->created_at->toIso8601String(),
            'updated_at' => $promotion->updated_at->toIso8601String(),
        ];
    }
}