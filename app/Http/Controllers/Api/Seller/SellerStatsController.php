<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Seller\SellerStatsResource;
use App\Models\Catalog\Category;
use App\Models\Catalog\Product;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Seller\SellerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Controller : SellerStatsController
 *
 * Fournit les statistiques du vendeur connecté.
 *
 * ⚠️ RÈGLES CRITIQUES DE SÉCURITÉ :
 *   - Filtre strict : order_items.seller_id = auth()->id()
 *   - Aucune donnée d'autres vendeurs n'est exposée
 *   - Export CSV limité aux données du vendeur
 *
 * Actions :
 *   - sales()     : CA, commandes, panier moyen + série temporelle
 *   - products()  : Top produits et catégories
 *   - customers() : Top clients + segmentation (nouveaux/fidèles)
 *   - export()    : Export CSV (commandes ou produits)
 *
 * @package App\Http\Controllers\Api\Seller
 */
class SellerStatsController extends Controller
{
    /**
     * Périodes acceptées (en jours). 0 = tout.
     */
    private const ALLOWED_PERIODS = [7, 30, 90, 0];

    // ============================================
    // SALES — STATISTIQUES DE VENTES
    // ============================================

    /**
     * GET /api/v1/seller/stats/sales?period=30
     *
     * Retourne les KPIs de ventes + la série temporelle.
     */
    public function sales(Request $request): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);
            $period   = $this->validatePeriod($request->input('period', 30));

            // ============================================
            // CALCUL DES DATES
            // ============================================
            [$startDate, $endDate, $prevStart, $prevEnd] = $this->computeDates($period);

            // ============================================
            // KPIs : PÉRIODE ACTUELLE
            // ============================================
            $current = $this->aggregateSales($sellerId, $startDate, $endDate);

            // ============================================
            // KPIs : PÉRIODE PRÉCÉDENTE (comparaison)
            // ============================================
            $previous = $this->aggregateSales($sellerId, $prevStart, $prevEnd);

            // ============================================
            // TIMELINE (série temporelle)
            // ============================================
            $timeline = $this->buildTimeline($sellerId, $period, $startDate, $endDate);

            // ============================================
            // CONSTRUCTION DE LA RÉPONSE
            // ============================================
            $data = [
                'total_revenue'              => $current['revenue'],
                'previous_revenue'           => $previous['revenue'],
                'total_orders'               => $current['orders'],
                'previous_orders'            => $previous['orders'],
                'total_items'                => $current['items'],
                'previous_items'             => $previous['items'],
                'average_order_value'        => $current['avg_order'],
                'previous_average_order_value'=> $previous['avg_order'],
                'timeline'                   => $timeline,
                'period_days'                => $period,
                'period_label'               => $this->getPeriodLabel($period),
                'start_date'                 => $startDate?->toDateString(),
                'end_date'                   => $endDate?->toDateString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistiques de ventes récupérées.',
                'data'    => new SellerStatsResource($data, 'sales'),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerStatsController::sales', ['error' => $e->getMessage()]);
            return $this->error('Impossible de récupérer les stats de ventes : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // PRODUCTS — TOP PRODUITS
    // ============================================

    /**
     * GET /api/v1/seller/stats/products?period=30&limit=5
     */
    public function products(Request $request): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);
            $period   = $this->validatePeriod($request->input('period', 30));
            $limit    = min((int) $request->input('limit', 5), 20);

            [$startDate, $endDate] = $this->computeDates($period);

            // ============================================
            // TOP PRODUITS (par revenu)
            // ============================================
            $topProductsQuery = OrderItem::query()
                ->where('seller_id', $sellerId)
                ->whereIn('item_status', ['delivered', 'shipped'])
                ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->select(
                    'product_id as id',
                    'product_name as name',
                    'product_sku as sku',
                    DB::raw('SUM(quantity) as quantity_sold'),
                    DB::raw('SUM(total_price) as revenue'),
                    DB::raw('COUNT(DISTINCT order_id) as orders_count')
                )
                ->groupBy('product_id', 'product_name', 'product_sku')
                ->orderByDesc('revenue')
                ->limit($limit);

            $topProducts = $topProductsQuery->get();

            // Enrichir avec les infos produit
            $productIds = $topProducts->pluck('id')->toArray();
            $productsInfo = Product::whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            $topProducts = $topProducts->map(function ($item) use ($productsInfo) {
                $product = $productsInfo->get($item->id);
                return [
                    'id'             => $item->id,
                    'name'           => $item->name,
                    'sku'            => $item->sku,
                    'main_image'     => $product?->main_image,
                    'quantity_sold'  => (int) $item->quantity_sold,
                    'revenue'        => (float) $item->revenue,
                    'orders_count'   => (int) $item->orders_count,
                    'average_rating' => (float) ($product?->average_rating ?? 0),
                    'views_count'    => (int) ($product?->views_count ?? 0),
                ];
            });

            // ============================================
            // TOP CATÉGORIES
            // ============================================
            $totalRevenue = $topProducts->sum('revenue');

            $topCategories = OrderItem::query()
                ->where('order_items.seller_id', $sellerId)
                ->whereIn('order_items.item_status', ['delivered', 'shipped'])
                ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('order_items.created_at', [$startDate, $endDate]);
                })
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->join('categories', 'categories.id', '=', 'products.category_id')
                ->select(
                    'categories.id as id',
                    'categories.name as name',
                    DB::raw('SUM(order_items.quantity) as products_sold'),
                    DB::raw('SUM(order_items.total_price) as revenue')
                )
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('revenue')
                ->limit(5)
                ->get()
                ->map(function ($cat) use ($totalRevenue) {
                    return [
                        'id'             => (int) $cat->id,
                        'name'           => $cat->name,
                        'products_sold'  => (int) $cat->products_sold,
                        'revenue'        => (float) $cat->revenue,
                        'percentage'     => $totalRevenue > 0
                            ? round(($cat->revenue / $totalRevenue) * 100, 1)
                            : 0,
                    ];
                });

            // ============================================
            // RÉSUMÉ
            // ============================================
            $totalProductsSold = OrderItem::where('seller_id', $sellerId)
                ->whereIn('item_status', ['delivered', 'shipped'])
                ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('quantity');

            $uniqueProducts = OrderItem::where('seller_id', $sellerId)
                ->whereIn('item_status', ['delivered', 'shipped'])
                ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->distinct('product_id')
                ->count('product_id');

            $data = [
                'top_products'        => $topProducts->toArray(),
                'top_categories'      => $topCategories->toArray(),
                'total_products_sold' => (int) $totalProductsSold,
                'unique_products'     => (int) $uniqueProducts,
                'total_revenue'       => (float) $totalRevenue,
                'period_days'         => $period,
                'period_label'        => $this->getPeriodLabel($period),
                'start_date'          => $startDate?->toDateString(),
                'end_date'            => $endDate?->toDateString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistiques produits récupérées.',
                'data'    => new SellerStatsResource($data, 'products'),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerStatsController::products', ['error' => $e->getMessage()]);
            return $this->error('Impossible de récupérer les stats produits : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // CUSTOMERS — TOP CLIENTS
    // ============================================

    /**
     * GET /api/v1/seller/stats/customers?period=30&limit=5
     */
    public function customers(Request $request): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);
            $period   = $this->validatePeriod($request->input('period', 30));
            $limit    = min((int) $request->input('limit', 5), 20);

            [$startDate, $endDate] = $this->computeDates($period);

            // ============================================
            // TOP CLIENTS (par total dépensé)
            // ============================================
            $topCustomers = OrderItem::query()
                ->where('order_items.seller_id', $sellerId)
                ->whereIn('order_items.item_status', ['delivered', 'shipped'])
                ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('order_items.created_at', [$startDate, $endDate]);
                })
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('users', 'users.id', '=', 'orders.user_id')
                ->select(
                    'users.id as id',
                    'users.name as name',
                    DB::raw('SUM(order_items.total_price) as total_spent'),
                    DB::raw('COUNT(DISTINCT orders.id) as orders_count'),
                    DB::raw('MAX(order_items.created_at) as last_order_at')
                )
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total_spent')
                ->limit($limit)
                ->get()
                ->map(function ($customer) {
                    $ordersCount = (int) $customer->orders_count;
                    $totalSpent = (float) $customer->total_spent;
                    $avgOrder = $ordersCount > 0 ? $totalSpent / $ordersCount : 0;

                    $lastOrderAt = $customer->last_order_at
                        ? \Carbon\Carbon::parse($customer->last_order_at)
                        : null;

                    return [
                        'id'             => (int) $customer->id,
                        'name'           => $customer->name,
                        'orders_count'   => $ordersCount,
                        'total_spent'    => $totalSpent,
                        'average_order'  => round($avgOrder, 2),
                        'last_order_at'  => $lastOrderAt?->toIso8601String(),
                        'last_order_human' => $lastOrderAt?->diffForHumans(),
                    ];
                });

            // ============================================
            // SEGMENTATION (nouveaux vs fidèles)
            // ============================================
            $allCustomerStats = OrderItem::query()
                ->where('order_items.seller_id', $sellerId)
                ->whereIn('order_items.item_status', ['delivered', 'shipped'])
                ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('order_items.created_at', [$startDate, $endDate]);
                })
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->select(
                    'orders.user_id as user_id',
                    DB::raw('COUNT(DISTINCT orders.id) as orders_count')
                )
                ->groupBy('orders.user_id')
                ->get();

            $totalCustomers = $allCustomerStats->count();
            $newCustomers = $allCustomerStats->where('orders_count', 1)->count();
            $returningCustomers = $allCustomerStats->where('orders_count', '>=', 2)->count();
            $loyalCustomers = $allCustomerStats->where('orders_count', '>=', 3)->count();

            // ============================================
            // RÉSUMÉ
            // ============================================
            $totalRevenue = OrderItem::where('seller_id', $sellerId)
                ->whereIn('item_status', ['delivered', 'shipped'])
                ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('total_price');

            $avgCustomerValue = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;

            $data = [
                'top_customers'         => $topCustomers->toArray(),
                'new_customers'         => $newCustomers,
                'returning_customers'   => $returningCustomers,
                'loyal_customers'       => $loyalCustomers,
                'total_customers'       => $totalCustomers,
                'new_percentage'        => $totalCustomers > 0
                    ? round(($newCustomers / $totalCustomers) * 100, 1) : 0,
                'returning_percentage'  => $totalCustomers > 0
                    ? round(($returningCustomers / $totalCustomers) * 100, 1) : 0,
                'loyal_percentage'      => $totalCustomers > 0
                    ? round(($loyalCustomers / $totalCustomers) * 100, 1) : 0,
                'total_revenue'         => (float) $totalRevenue,
                'average_customer_value'=> round($avgCustomerValue, 2),
                'period_days'           => $period,
                'period_label'          => $this->getPeriodLabel($period),
                'start_date'            => $startDate?->toDateString(),
                'end_date'              => $endDate?->toDateString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistiques clients récupérées.',
                'data'    => new SellerStatsResource($data, 'customers'),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerStatsController::customers', ['error' => $e->getMessage()]);
            return $this->error('Impossible de récupérer les stats clients : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // EXPORT — CSV
    // ============================================

    /**
     * GET /api/v1/seller/stats/export?type=orders&period=30
     *
     * Exporte les données en CSV.
     *
     * Utilise `stream()` pour éviter de charger tout en mémoire.
     *
     * @param Request $request
     * @return StreamedResponse|JsonResponse
     */
    public function export(Request $request)
    {
        try {
            $sellerId = $this->resolveSellerId($request);
            $type     = $request->input('type', 'orders');
            $period   = $this->validatePeriod($request->input('period', 30));

            if (!in_array($type, ['orders', 'products'], true)) {
                return $this->error('Type d\'export invalide. Utilisez "orders" ou "products".', Response::HTTP_BAD_REQUEST);
            }

            [$startDate, $endDate] = $this->computeDates($period);

            $filename = "shoppro-{$type}-" . now()->format('Y-m-d-His') . ".csv";

            return response()->streamDownload(
                function () use ($sellerId, $type, $startDate, $endDate) {
                    $handle = fopen('php://output', 'w');

                    // BOM UTF-8 pour Excel
                    fwrite($handle, "\xEF\xBB\xBF");

                    if ($type === 'orders') {
                        $this->exportOrders($handle, $sellerId, $startDate, $endDate);
                    } else {
                        $this->exportProducts($handle, $sellerId, $startDate, $endDate);
                    }

                    fclose($handle);
                },
                $filename,
                [
                    'Content-Type'        => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                    'X-Accel-Buffering'   => 'no', // Anti-buffering pour nginx
                ]
            );
        } catch (Throwable $e) {
            Log::error('SellerStatsController::export', ['error' => $e->getMessage()]);
            return $this->error('Impossible d\'exporter : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Exporte les commandes en CSV (streamé).
     */
    private function exportOrders($handle, int $sellerId, $startDate, $endDate): void
    {
        // En-têtes
        fputcsv($handle, [
            'N° Commande',
            'Date',
            'Client',
            'Ville',
            'Produits',
            'Quantité',
            'Montant Total (FCFA)',
            'Statut',
            'Statut Paiement',
        ], ';');

        // Streaming des commandes
        OrderItem::query()
            ->where('order_items.seller_id', $sellerId)
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('order_items.created_at', [$startDate, $endDate]);
            })
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->leftJoin('user_addresses', 'user_addresses.id', '=', 'orders.shipping_address_id')
            ->select(
                'orders.order_number',
                'orders.created_at',
                'orders.status',
                'orders.payment_status',
                'users.name as customer_name',
                'user_addresses.city',
                'order_items.product_name',
                'order_items.quantity',
                'order_items.total_price'
            )
            ->orderBy('orders.created_at', 'desc')
            ->chunk(500, function ($orders) use ($handle) {
                foreach ($orders as $order) {
                    fputcsv($handle, [
                        $order->order_number,
                        \Carbon\Carbon::parse($order->created_at)->format('Y-m-d H:i'),
                        $order->customer_name,
                        $order->city ?? 'N/A',
                        $order->product_name,
                        $order->quantity,
                        number_format($order->total_price, 2, '.', ''),
                        $order->status,
                        $order->payment_status,
                    ], ';');
                }
            });
    }

    /**
     * Exporte les produits en CSV (streamé).
     */
    private function exportProducts($handle, int $sellerId, $startDate, $endDate): void
    {
        // En-têtes
        fputcsv($handle, [
            'SKU',
            'Nom du produit',
            'Quantité vendue',
            'Commandes',
            'Revenu (FCFA)',
            'Prix moyen',
        ], ';');

        // Streaming des produits
        OrderItem::query()
            ->where('order_items.seller_id', $sellerId)
            ->whereIn('order_items.item_status', ['delivered', 'shipped'])
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('order_items.created_at', [$startDate, $endDate]);
            })
            ->select(
                'order_items.product_sku',
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count'),
                DB::raw('SUM(order_items.total_price) as revenue')
            )
            ->groupBy('order_items.product_sku', 'order_items.product_name')
            ->orderByDesc('revenue')
            ->chunk(500, function ($products) use ($handle) {
                foreach ($products as $product) {
                    $avgPrice = $product->total_quantity > 0
                        ? $product->revenue / $product->total_quantity
                        : 0;

                    fputcsv($handle, [
                        $product->product_sku,
                        $product->product_name,
                        $product->total_quantity,
                        $product->orders_count,
                        number_format($product->revenue, 2, '.', ''),
                        number_format($avgPrice, 2, '.', ''),
                    ], ';');
                }
            });
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
     * Valide la période.
     */
    private function validatePeriod($period): int
    {
        $period = (int) $period;
        return in_array($period, self::ALLOWED_PERIODS, true) ? $period : 30;
    }

    /**
     * Calcule les dates de début/fin (période actuelle + précédente).
     *
     * @return array{0: ?\Carbon\Carbon, 1: ?\Carbon\Carbon, 2: ?\Carbon\Carbon, 3: ?\Carbon\Carbon}
     */
    private function computeDates(int $period): array
    {
        if ($period === 0) {
            // "Tout" → pas de dates
            return [null, null, null, null];
        }

        $endDate = now()->endOfDay();
        $startDate = now()->subDays($period - 1)->startOfDay();

        // Période précédente (même durée)
        $prevEnd = $startDate->copy()->subSecond();
        $prevStart = $prevEnd->copy()->subDays($period - 1)->startOfDay();

        return [$startDate, $endDate, $prevStart, $prevEnd];
    }

    /**
     * Agrège les stats de ventes pour une période.
     *
     * @return array{revenue: float, orders: int, items: int, avg_order: float}
     */
    private function aggregateSales(int $sellerId, $startDate, $endDate): array
    {
        $query = OrderItem::query()
            ->where('seller_id', $sellerId)
            ->whereIn('item_status', ['delivered', 'shipped'])
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            });

        $revenue = (float) (clone $query)->sum('total_price');
        $orders = (int) (clone $query)->distinct('order_id')->count('order_id');
        $items = (int) (clone $query)->sum('quantity');
        $avgOrder = $orders > 0 ? $revenue / $orders : 0;

        return [
            'revenue'   => $revenue,
            'orders'    => $orders,
            'items'     => $items,
            'avg_order' => round($avgOrder, 2),
        ];
    }

    /**
     * Construit la série temporelle pour le graphique.
     */
    private function buildTimeline(int $sellerId, int $period, $startDate, $endDate): array
    {
        // Si "tout", utiliser 30 derniers jours par défaut
        $days = $period > 0 ? $period : 30;
        $start = $startDate ?? now()->subDays($days - 1)->startOfDay();
        $end = $endDate ?? now()->endOfDay();

        // Récupérer les ventes par jour
        $salesByDay = OrderItem::query()
            ->where('seller_id', $sellerId)
            ->whereIn('item_status', ['delivered', 'shipped'])
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, SUM(total_price) as revenue, COUNT(DISTINCT order_id) as orders, SUM(quantity) as items')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Générer tous les jours
        $timeline = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $dateStr = $date->toDateString();
            $dayData = $salesByDay->get($dateStr);

            $timeline[] = [
                'date'           => $dateStr,
                'date_formatted' => $date->format('d/m'),
                'day_name'       => ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'][$date->dayOfWeek],
                'revenue'        => (float) ($dayData->revenue ?? 0),
                'orders'         => (int) ($dayData->orders ?? 0),
                'items'          => (int) ($dayData->items ?? 0),
            ];
        }

        return $timeline;
    }

    /**
     * Retourne le label de la période.
     */
    private function getPeriodLabel(int $period): string
    {
        return match ($period) {
            7   => '7 derniers jours',
            30  => '30 derniers jours',
            90  => '90 derniers jours',
            0   => 'Toute la période',
            default => "{$period} jours",
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