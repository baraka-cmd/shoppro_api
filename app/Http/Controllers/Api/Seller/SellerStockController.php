<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Seller\SellerStockResource;
use App\Models\Catalog\Product;
use App\Models\Catalog\Stock;
use App\Models\Catalog\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Controller : SellerStockController
 *
 * Gère les stocks des produits du vendeur connecté.
 *
 * ⚠️ RÈGLE CRITIQUE DE COHÉRENCE :
 *   Le stock d'un produit existe dans 2 tables :
 *     1. `stocks.quantity`       (gestion détaillée par entrepôt)
 *     2. `products.stock`         (dénormalisé, visible côté client)
 *
 *   Quand le vendeur ajuste `stocks.quantity`, on DOIT synchroniser
 *   `products.stock` dans la MÊME transaction. Sinon le client voit
 *   un stock faux, ce qui cause :
 *     - Ventes de produits indisponibles
 *     - Annulations en cascade
 *     - Avis clients négatifs
 *
 * Sécurité :
 *   - Middleware : auth:sanctum + role:seller,admin + seller.active
 *   - Scope strict : stock.seller_id === auth()->id()
 *   - Aucune fuite de stocks d'autres vendeurs
 *
 * Actions :
 *   - index()     : Liste paginée des stocks avec filtres et stats
 *   - show()      : Détail d'un stock + mouvements récents
 *   - adjust()    : Ajustement manuel (avec sync products.stock)
 *   - movements() : Historique paginé des mouvements
 *
 * @package App\Http\Controllers\Api\Seller
 */
class SellerStockController extends Controller
{
    // ============================================
    // INDEX — LISTE DES STOCKS
    // ============================================

    /**
     * GET /api/v1/seller/stocks
     *
     * Liste paginée des stocks du vendeur.
     *
     * Query params :
     *   - status       : all | in_stock | low_stock | out_of_stock
     *   - search       : recherche par nom ou SKU produit
     *   - warehouse_id : filtrer par entrepôt
     *   - sort_by      : updated_at | quantity | available_quantity
     *   - sort_dir     : asc | desc
     *   - per_page     : 20 par défaut, max 50
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);

            $query = Stock::query()
                ->where('seller_id', $sellerId)
                ->with(['product', 'warehouse']);

            // ============================================
            // FILTRE : Statut
            // ============================================
            $status = $request->input('status', 'all');
            switch ($status) {
                case 'out_of_stock':
                    $query->where('available_quantity', '<=', 0);
                    break;
                case 'low_stock':
                    $query->where('available_quantity', '>', 0)
                          ->whereColumn('available_quantity', '<=', 'min_quantity');
                    break;
                case 'in_stock':
                    $query->whereColumn('available_quantity', '>', 'min_quantity');
                    break;
                // 'all' → pas de filtre
            }

            // ============================================
            // FILTRE : Entrepôt
            // ============================================
            if ($request->filled('warehouse_id')) {
                $query->where('warehouse_id', (int) $request->input('warehouse_id'));
            }

            // ============================================
            // RECHERCHE (nom produit ou SKU)
            // ============================================
            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // ============================================
            // TRI
            // ============================================
            $sortBy = $request->input('sort_by', 'updated_at');
            $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

            $allowedSorts = ['updated_at', 'quantity', 'available_quantity', 'min_quantity'];
            if (!in_array($sortBy, $allowedSorts, true)) {
                $sortBy = 'updated_at';
            }

            $query->orderBy($sortBy, $sortDir);

            // ============================================
            // PAGINATION
            // ============================================
            $perPage = min((int) $request->input('per_page', 20), 50);
            $stocks = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Stocks récupérés avec succès.',
                'data'    => SellerStockResource::collection($stocks),
                'pagination' => [
                    'total'        => $stocks->total(),
                    'count'        => $stocks->count(),
                    'per_page'     => $stocks->perPage(),
                    'current_page' => $stocks->currentPage(),
                    'last_page'    => $stocks->lastPage(),
                    'has_more'     => $stocks->hasMorePages(),
                ],
                'stats' => $this->buildStats($sellerId),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerStockController::index', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de récupérer les stocks : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // SHOW — DÉTAIL
    // ============================================

    /**
     * GET /api/v1/seller/stocks/{stock}
     *
     * Détail d'un stock + derniers mouvements.
     */
    public function show(Request $request, Stock $stock): JsonResponse
    {
        try {
            // Vérifier la propriété (scope strict)
            if ($stock->seller_id !== $this->resolveSellerId($request) &&
                !$request->user()->isAdmin()) {
                return $this->error(
                    'Vous n\'êtes pas autorisé à voir ce stock.',
                    Response::HTTP_FORBIDDEN
                );
            }

            $stock->load(['product', 'warehouse']);

            // Charger les derniers mouvements
            $recentMovements = StockMovement::query()
                ->where('product_id', $stock->product_id)
                ->where('warehouse_id', $stock->warehouse_id)
                ->latest()
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Stock récupéré avec succès.',
                'data'    => new SellerStockResource($stock),
                'recent_movements' => $this->formatMovements($recentMovements),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerStockController::show', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de récupérer le stock : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // ADJUST — AJUSTEMENT MANUEL
    // ============================================

    /**
     * POST /api/v1/seller/stocks/{stock}/adjust
     *
     * Ajuste manuellement le stock avec raison et synchronise
     * automatiquement `products.stock`.
     *
     * Body :
     *   - quantity    : nouvelle quantité absolue (required, min:0)
     *   - reason      : 'inventory' | 'damage' | 'return' | 'correction' | 'other'
     *   - note        : optionnel (max 500)
     */
    public function adjust(Request $request, Stock $stock): JsonResponse
    {
        try {
            // Vérifier la propriété
            if ($stock->seller_id !== $this->resolveSellerId($request) &&
                !$request->user()->isAdmin()) {
                return $this->error(
                    'Vous n\'êtes pas autorisé à modifier ce stock.',
                    Response::HTTP_FORBIDDEN
                );
            }

            // Validation
            $validated = $request->validate([
                'quantity' => 'required|integer|min:0|max:999999',
                'reason'   => 'required|string|in:inventory,damage,return,correction,other',
                'note'     => 'nullable|string|max:500',
            ], [
                'quantity.required' => 'La nouvelle quantité est obligatoire.',
                'quantity.integer'  => 'La quantité doit être un nombre entier.',
                'quantity.min'      => 'La quantité ne peut pas être négative.',
                'quantity.max'      => 'La quantité ne peut pas dépasser 999 999.',
                'reason.required'   => 'La raison de l\'ajustement est obligatoire.',
                'reason.in'         => 'La raison sélectionnée n\'est pas valide.',
                'note.max'          => 'La note ne peut pas dépasser 500 caractères.',
            ]);

            DB::beginTransaction();

            $oldQuantity = (int) $stock->quantity;
            $newQuantity = (int) $validated['quantity'];
            $difference = $newQuantity - $oldQuantity;

            // Rien à faire si la quantité n'a pas changé
            if ($difference === 0) {
                DB::rollBack();
                return $this->error(
                    'La quantité est identique. Aucun ajustement nécessaire.',
                    Response::HTTP_CONFLICT
                );
            }

            // ============================================
            // 1. Mettre à jour le stock détaillé
            // ============================================
            $stock->update([
                'quantity'           => $newQuantity,
                'available_quantity' => $newQuantity - (int) $stock->reserved_quantity,
                'last_counted_at'    => now(),
            ]);

            // ============================================
            // 2. Synchroniser products.stock (CRITIQUE)
            // ============================================
            $product = Product::find($stock->product_id);
            if ($product) {
                // Recalculer le stock total du produit (somme de tous les entrepôts)
                $totalStock = Stock::where('product_id', $product->id)
                    ->where('is_active', true)
                    ->sum('available_quantity');

                $product->update([
                    'stock' => max(0, (int) $totalStock),
                ]);
            }

            // ============================================
            // 3. Créer le mouvement de stock (audit)
            // ============================================
            StockMovement::create([
                'product_id'         => $stock->product_id,
                'product_variant_id' => $stock->product_variant_id,
                'warehouse_id'       => $stock->warehouse_id,
                'user_id'            => $request->user()->id,
                'type'               => $this->mapReasonToMovementType($validated['reason']),
                'quantity'           => $difference,
                'quantity_before'    => $oldQuantity,
                'quantity_after'     => $newQuantity,
                'note'               => $validated['note'] ?? $this->getDefaultNote($validated['reason']),
            ]);

            DB::commit();

            Log::info('SellerStock adjusted', [
                'stock_id'     => $stock->id,
                'seller_id'    => $stock->seller_id,
                'product_id'   => $stock->product_id,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'difference'   => $difference,
                'reason'       => $validated['reason'],
            ]);

            $stock->refresh()->load(['product', 'warehouse']);

            return response()->json([
                'success' => true,
                'message' => "Stock ajusté de {$difference} unité(s).",
                'data'    => new SellerStockResource($stock),
                'adjustment' => [
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                    'difference'   => $difference,
                    'reason'       => $validated['reason'],
                ],
            ], Response::HTTP_OK);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerStockController::adjust', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error(
                'Impossible d\'ajuster le stock : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // MOVEMENTS — HISTORIQUE
    // ============================================

    /**
     * GET /api/v1/seller/stocks/movements
     *
     * Historique paginé des mouvements de stock du vendeur.
     *
     * Query params :
     *   - product_id  : filtrer par produit
     *   - type        : filtrer par type (in, out, adjustment, sale, return...)
     *   - date_from   : Y-m-d
     *   - date_to     : Y-m-d
     *   - per_page    : 20 par défaut
     */
    public function movements(Request $request): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);

            // Récupérer les IDs des produits du vendeur
            $sellerProductIds = Product::where('seller_id', $sellerId)
                ->pluck('id')
                ->toArray();

            $query = StockMovement::query()
                ->whereIn('product_id', $sellerProductIds)
                ->with(['product', 'warehouse']);

            // ============================================
            // FILTRE : Produit
            // ============================================
            if ($request->filled('product_id')) {
                $productId = (int) $request->input('product_id');
                if (in_array($productId, $sellerProductIds, true)) {
                    $query->where('product_id', $productId);
                }
            }

            // ============================================
            // FILTRE : Type
            // ============================================
            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            // ============================================
            // FILTRE : Date
            // ============================================
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }

            // ============================================
            // PAGINATION
            // ============================================
            $perPage = min((int) $request->input('per_page', 20), 50);
            $movements = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Mouvements récupérés avec succès.',
                'data'    => $this->formatMovements($movements->items()),
                'pagination' => [
                    'total'        => $movements->total(),
                    'count'        => $movements->count(),
                    'per_page'     => $movements->perPage(),
                    'current_page' => $movements->currentPage(),
                    'last_page'    => $movements->lastPage(),
                    'has_more'     => $movements->hasMorePages(),
                ],
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerStockController::movements', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de récupérer les mouvements : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // HELPERS PRIVÉS
    // ============================================

    /**
     * Résout l'ID du vendeur cible.
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
     * Calcule les statistiques globales du stock vendeur.
     */
    private function buildStats(int $sellerId): array
    {
        $baseQuery = Stock::where('seller_id', $sellerId);

        $total = (clone $baseQuery)->count();
        $outOfStock = (clone $baseQuery)->where('available_quantity', '<=', 0)->count();
        $lowStock = (clone $baseQuery)
            ->where('available_quantity', '>', 0)
            ->whereColumn('available_quantity', '<=', 'min_quantity')
            ->count();
        $inStock = (clone $baseQuery)
            ->whereColumn('available_quantity', '>', 'min_quantity')
            ->count();

        $totalQuantity = (clone $baseQuery)->sum('quantity');
        $totalAvailable = (clone $baseQuery)->sum('available_quantity');
        $totalReserved = (clone $baseQuery)->sum('reserved_quantity');

        return [
            'total'            => $total,
            'in_stock'         => $inStock,
            'low_stock'        => $lowStock,
            'out_of_stock'     => $outOfStock,
            'needs_attention'  => $lowStock + $outOfStock,
            'total_quantity'   => (int) $totalQuantity,
            'total_available'  => (int) $totalAvailable,
            'total_reserved'   => (int) $totalReserved,
        ];
    }

    /**
     * Formate les mouvements pour l'API.
     */
    private function formatMovements($movements): array
    {
        return collect($movements)->map(function ($movement) {
            return [
                'id'              => $movement->id,
                'type'            => $movement->type,
                'type_label'      => $this->getMovementTypeLabel($movement->type),
                'type_color'      => $this->getMovementTypeColor($movement->type),
                'quantity'        => (int) $movement->quantity,
                'quantity_before' => (int) $movement->quantity_before,
                'quantity_after'  => (int) $movement->quantity_after,
                'note'            => $movement->note,
                'product' => $movement->product ? [
                    'id'   => $movement->product->id,
                    'name' => $movement->product->name,
                    'sku'  => $movement->product->sku,
                ] : null,
                'warehouse' => $movement->warehouse ? [
                    'id'   => $movement->warehouse->id,
                    'name' => $movement->warehouse->name,
                ] : null,
                'created_at'       => $movement->created_at?->toIso8601String(),
                'created_at_human' => $movement->created_at?->diffForHumans(),
            ];
        })->toArray();
    }

    /**
     * Mappe une raison d'ajustement à un type de mouvement.
     */
    private function mapReasonToMovementType(string $reason): string
    {
        return match ($reason) {
            'inventory'  => 'adjustment',
            'damage'     => 'out',
            'return'     => 'return',
            'correction' => 'adjustment',
            'other'      => 'adjustment',
            default      => 'adjustment',
        };
    }

    /**
     * Note par défaut selon la raison.
     */
    private function getDefaultNote(string $reason): string
    {
        return match ($reason) {
            'inventory'  => 'Inventaire physique',
            'damage'     => 'Produit endommagé',
            'return'     => 'Retour client',
            'correction' => 'Correction manuelle',
            'other'      => 'Ajustement manuel',
            default      => 'Ajustement',
        };
    }

    /**
     * Label lisible du type de mouvement.
     */
    private function getMovementTypeLabel(string $type): string
    {
        return match ($type) {
            'in'         => 'Entrée',
            'out'        => 'Sortie',
            'adjustment' => 'Ajustement',
            'transfer'   => 'Transfert',
            'return'     => 'Retour',
            'sale'       => 'Vente',
            'purchase'   => 'Achat',
            default      => ucfirst($type),
        };
    }

    /**
     * Couleur hexadécimale du type de mouvement.
     */
    private function getMovementTypeColor(string $type): string
    {
        return match ($type) {
            'in', 'return', 'purchase' => '#10B981', // success
            'out', 'sale'              => '#EF4444', // error
            'adjustment', 'transfer'   => '#F59E0B', // warning
            default                    => '#6B7280', // gray
        };
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