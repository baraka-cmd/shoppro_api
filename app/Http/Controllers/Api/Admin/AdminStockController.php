<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Stock;
use App\Models\Catalog\StockMovement;
use App\Models\Catalog\Product;
use App\Models\Catalog\Warehouse;
use App\Models\Catalog\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminStockController extends Controller
{
    /**
     * Récupérer la liste des stocks.
     *
     * Endpoint : GET /api/v1/admin/stocks
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Stock::with(['product', 'warehouse', 'variant'])
                ->orderByDesc('updated_at');

            // Filtres
            if ($request->has('product_id')) {
                $query->where('product_id', $request->query('product_id'));
            }

            if ($request->has('low_stock') && $request->query('low_stock') == 'true') {
                $query->lowStock();
            }

            if ($request->has('out_of_stock') && $request->query('out_of_stock') == 'true') {
                $query->outOfStock();
            }

            if ($request->has('search') && !empty($request->query('search'))) {
                $search = $request->query('search');
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('sku', 'LIKE', "%{$search}%");
                });
            }

            $stocks = $query->get();

            $data = $stocks->map(function ($stock) {
                return [
                    'id' => $stock->id,
                    'product_id' => $stock->product_id,
                    'product_name' => $stock->product ? $stock->product->name : 'Produit',
                    'product_sku' => $stock->product ? $stock->product->sku : null,
                    'product_image' => $stock->product ? $stock->product->main_image : null,
                    'warehouse_id' => $stock->warehouse_id,
                    'warehouse_name' => $stock->warehouse ? $stock->warehouse->name : 'Entrepôt',
                    'product_variant_id' => $stock->product_variant_id,
                    'variant_name' => $stock->variant ? $stock->variant->name : null,
                    'quantity' => (int) $stock->quantity,
                    'reserved_quantity' => (int) $stock->reserved_quantity,
                    'available_quantity' => (int) $stock->available_quantity,
                    'min_quantity' => (int) $stock->min_quantity,
                    'max_quantity' => $stock->max_quantity ? (int) $stock->max_quantity : null,
                    'location' => $stock->location,
                    'shelf' => $stock->shelf,
                    'bin' => $stock->bin,
                    'unit_cost' => $stock->unit_cost ? (float) $stock->unit_cost : null,
                    'total_value' => $stock->total_value ? (float) $stock->total_value : null,
                    'last_counted_at' => $stock->last_counted_at ? $stock->last_counted_at->toDateString() : null,
                    'expiry_date' => $stock->expiry_date ? $stock->expiry_date->toDateString() : null,
                    'is_active' => (bool) $stock->is_active,
                    'is_low_stock' => (bool) $stock->is_low_stock,
                    'is_out_of_stock' => (bool) $stock->is_out_of_stock,
                    'created_at' => $stock->created_at ? $stock->created_at->toIso8601String() : null,
                    'updated_at' => $stock->updated_at ? $stock->updated_at->toIso8601String() : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Stocks récupérés avec succès',
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des stocks',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les détails d'un stock.
     *
     * Endpoint : GET /api/v1/admin/stocks/{id}
     */
    public function show($id)
    {
        try {
            $stock = Stock::with(['product', 'warehouse', 'variant'])
                ->find($id);

            if (!$stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock introuvable',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stock récupéré avec succès',
                'data' => [
                    'id' => $stock->id,
                    'product_id' => $stock->product_id,
                    'product_name' => $stock->product ? $stock->product->name : 'Produit',
                    'warehouse_name' => $stock->warehouse ? $stock->warehouse->name : 'Entrepôt',
                    'quantity' => (int) $stock->quantity,
                    'reserved_quantity' => (int) $stock->reserved_quantity,
                    'available_quantity' => (int) $stock->available_quantity,
                    'min_quantity' => (int) $stock->min_quantity,
                    'is_low_stock' => (bool) $stock->is_low_stock,
                    'is_out_of_stock' => (bool) $stock->is_out_of_stock,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du stock',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les stocks faibles.
     *
     * Endpoint : GET /api/v1/admin/stocks/low
     */
    public function lowStock()
    {
        try {
            $stocks = Stock::with('product')
                ->lowStock()
                ->get()
                ->map(function ($stock) {
                    return [
                        'id' => $stock->id,
                        'product_name' => $stock->product ? $stock->product->name : 'Produit',
                        'quantity' => (int) $stock->quantity,
                        'min_quantity' => (int) $stock->min_quantity,
                        'available_quantity' => (int) $stock->available_quantity,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Stocks faibles récupérés',
                'data' => $stocks,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des stocks faibles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les stocks en rupture.
     *
     * Endpoint : GET /api/v1/admin/stocks/out
     */
    public function outOfStock()
    {
        try {
            $stocks = Stock::with('product')
                ->outOfStock()
                ->get()
                ->map(function ($stock) {
                    return [
                        'id' => $stock->id,
                        'product_name' => $stock->product ? $stock->product->name : 'Produit',
                        'quantity' => 0,
                        'min_quantity' => (int) $stock->min_quantity,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Stocks en rupture récupérés',
                'data' => $stocks,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des stocks en rupture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les mouvements de stock.
     *
     * Endpoint : GET /api/v1/admin/stocks/movements
     */
    public function movements(Request $request)
    {
        try {
            $query = StockMovement::with(['product', 'warehouse', 'user'])
                ->orderByDesc('created_at');

            if ($request->has('product_id')) {
                $query->where('product_id', $request->query('product_id'));
            }

            if ($request->has('type')) {
                $query->where('type', $request->query('type'));
            }

            if ($request->has('limit')) {
                $query->limit($request->query('limit'));
            }

            $movements = $query->get()->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'product_id' => $movement->product_id,
                    'product_name' => $movement->product ? $movement->product->name : 'Produit',
                    'warehouse_id' => $movement->warehouse_id,
                    'warehouse_name' => $movement->warehouse ? $movement->warehouse->name : 'Entrepôt',
                    'user_id' => $movement->user_id,
                    'user_name' => $movement->user ? $movement->user->name : 'Système',
                    'type' => $movement->type,
                    'quantity' => (int) $movement->quantity,
                    'quantity_before' => (int) $movement->quantity_before,
                    'quantity_after' => (int) $movement->quantity_after,
                    'unit_price' => $movement->unit_price ? (float) $movement->unit_price : null,
                    'total_price' => $movement->total_price ? (float) $movement->total_price : null,
                    'reference' => $movement->reference,
                    'note' => $movement->note,
                    'created_at' => $movement->created_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Mouvements de stock récupérés',
                'data' => $movements,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des mouvements',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Réserver du stock.
     *
     * Endpoint : POST /api/v1/admin/stocks/{id}/reserve
     */
    public function reserve(Request $request, $id)
    {
        try {
            $stock = Stock::find($id);

            if (!$stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock introuvable',
                ], 404);
            }

            $quantity = (int) $request->input('quantity', 1);

            if ($quantity <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'La quantité doit être positive',
                ], 422);
            }

            // ✅ Utiliser la méthode du modèle
            if (!$stock->reserve($quantity)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock disponible insuffisant',
                ], 422);
            }

            // Enregistrer le mouvement
            StockMovement::create([
                'product_id' => $stock->product_id,
                'product_variant_id' => $stock->product_variant_id,
                'warehouse_id' => $stock->warehouse_id,
                'user_id' => auth()->id(),
                'type' => 'reserve',
                'quantity' => $quantity,
                'quantity_before' => $stock->quantity - $quantity,
                'quantity_after' => $stock->quantity,
                'note' => $request->input('note', 'Réservation manuelle'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stock réservé avec succès',
                'data' => $stock,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réservation du stock',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Libérer du stock réservé.
     *
     * Endpoint : POST /api/v1/admin/stocks/{id}/release
     */
    public function release(Request $request, $id)
    {
        try {
            $stock = Stock::find($id);

            if (!$stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock introuvable',
                ], 404);
            }

            $quantity = (int) $request->input('quantity', 1);

            if ($quantity <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'La quantité doit être positive',
                ], 422);
            }

            // ✅ Utiliser la méthode du modèle
            if (!$stock->release($quantity)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantité réservée insuffisante',
                ], 422);
            }

            StockMovement::create([
                'product_id' => $stock->product_id,
                'product_variant_id' => $stock->product_variant_id,
                'warehouse_id' => $stock->warehouse_id,
                'user_id' => auth()->id(),
                'type' => 'release',
                'quantity' => $quantity,
                'quantity_before' => $stock->quantity + $quantity,
                'quantity_after' => $stock->quantity,
                'note' => $request->input('note', 'Libération manuelle'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stock libéré avec succès',
                'data' => $stock,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la libération du stock',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ajuster le stock.
     *
     * Endpoint : POST /api/v1/admin/stocks/{id}/adjust
     */
    public function adjust(Request $request, $id)
    {
        try {
            $stock = Stock::find($id);

            if (!$stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock introuvable',
                ], 404);
            }

            $newQuantity = (int) $request->input('new_quantity');

            if ($newQuantity < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'La quantité ne peut pas être négative',
                ], 422);
            }

            $quantityBefore = $stock->quantity;

            // ✅ Utiliser la méthode du modèle
            $stock->adjust($newQuantity, $request->input('note', 'Ajustement manuel'));

            return response()->json([
                'success' => true,
                'message' => 'Stock ajusté avec succès',
                'data' => $stock,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajustement du stock',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les statistiques des stocks.
     *
     * Endpoint : GET /api/v1/admin/stocks/stats
     */
    public function stats()
    {
        try {
            $totalStocks = Stock::count();
            $totalQuantity = Stock::sum('quantity') ?? 0;
            $totalReserved = Stock::sum('reserved_quantity') ?? 0;
            $totalAvailable = Stock::sum('available_quantity') ?? 0;
            $lowStocks = Stock::lowStock()->count();
            $outOfStocks = Stock::outOfStock()->count();

            return response()->json([
                'success' => true,
                'message' => 'Statistiques des stocks récupérées',
                'data' => [
                    'total_stocks' => (int) $totalStocks,
                    'total_quantity' => (int) $totalQuantity,
                    'total_reserved' => (int) $totalReserved,
                    'total_available' => (int) $totalAvailable,
                    'low_stocks' => (int) $lowStocks,
                    'out_of_stocks' => (int) $outOfStocks,
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
}