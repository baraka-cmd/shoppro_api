<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Seller\SellerDashboardResource;
use App\Models\Catalog\Product;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Review\Review;
use App\Models\Seller\SellerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Controller : SellerDashboardController
 *
 * Fournit les données du dashboard vendeur.
 */
class SellerDashboardController extends Controller
{
    private const CACHE_TTL = 300;
    private const CACHE_PREFIX = 'seller:dashboard';

    public function index(Request $request): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);
            $profile  = SellerProfile::where('user_id', $sellerId)->first();

            if (!$profile) {
                return $this->error('Profil vendeur introuvable.', Response::HTTP_NOT_FOUND);
            }

            // On convertit en tableau pour le cache
            $profileData = $profile->toArray();
            $cacheKey = $this->cacheKey('index', $sellerId);
            $fromCache = Cache::has($cacheKey);

            $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($profileData, $sellerId) {
                return [
                    'seller'        => $profileData,
                    'kpis'          => $this->buildKpis($sellerId, $profileData),
                    'alerts'        => $this->buildAlerts($sellerId, $profileData),
                    'recent_orders' => $this->fetchRecentOrdersArray($sellerId, 5),
                    'top_products'  => $this->fetchTopProductsArray($sellerId, 5),
                    'from_cache'    => false,
                ];
            });

            $data['from_cache'] = $fromCache;

            return $this->success(
                'Dashboard récupéré avec succès.',
                new SellerDashboardResource($data)
            );
        } catch (Throwable $e) {
            Log::error('SellerDashboardController::index', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->error('Impossible de récupérer le dashboard : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function salesChart(Request $request): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);
            $period   = $this->validatePeriod($request->input('period', 30));

            $cacheKey = $this->cacheKey("chart:$period", $sellerId);
            $fromCache = Cache::has($cacheKey);

            $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($sellerId, $period) {
                return $this->buildSalesChart($sellerId, $period);
            });

            return response()->json([
                'success'    => true,
                'message'    => 'Graphique récupéré avec succès.',
                'data'       => $data,
                'from_cache' => $fromCache,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerDashboardController::salesChart', ['error' => $e->getMessage()]);
            return $this->error('Impossible de récupérer le graphique : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function recentOrders(Request $request): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);
            $limit    = min((int) $request->input('limit', 10), 50);

            $orders = $this->fetchRecentOrdersArray($sellerId, $limit);

            return response()->json([
                'success' => true,
                'message' => 'Commandes récentes récupérées.',
                'data'    => $orders,
                'count'   => count($orders),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerDashboardController::recentOrders', ['error' => $e->getMessage()]);
            return $this->error('Impossible de récupérer les commandes récentes.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function topProducts(Request $request): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);
            $limit    = min((int) $request->input('limit', 5), 20);

            $products = $this->fetchTopProductsArray($sellerId, $limit);

            return response()->json([
                'success' => true,
                'message' => 'Top produits récupérés.',
                'data'    => $products,
                'count'   => count($products),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerDashboardController::topProducts', ['error' => $e->getMessage()]);
            return $this->error('Impossible de récupérer les top produits.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function alerts(Request $request): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);
            $profile  = SellerProfile::where('user_id', $sellerId)->first();

            if (!$profile) {
                return $this->error('Profil vendeur introuvable.', Response::HTTP_NOT_FOUND);
            }

            $alerts = $this->buildAlerts($sellerId, $profile->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Alertes récupérées.',
                'data'    => $alerts,
                'count'   => count($alerts),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerDashboardController::alerts', ['error' => $e->getMessage()]);
            return $this->error('Impossible de récupérer les alertes.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // HELPERS : KPIs
    // ============================================

    private function buildKpis(int $sellerId, array $profileData): array
    {
        $now       = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        $baseRevenueQuery = OrderItem::query()
            ->where('seller_id', $sellerId)
            ->whereIn('item_status', ['delivered', 'shipped']);

        $totalRevenue = (clone $baseRevenueQuery)->sum('total_price');

        $thisMonthRevenue = (clone $baseRevenueQuery)
            ->whereHas('order', fn ($q) => $q->where('created_at', '>=', $startOfMonth))
            ->sum('total_price');

        $lastMonthRevenue = (clone $baseRevenueQuery)
            ->whereHas('order', fn ($q) => $q->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth]))
            ->sum('total_price');

        $growthPercent = $lastMonthRevenue > 0
            ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : ($thisMonthRevenue > 0 ? 100 : 0);

        $commissionRate = (float) ($profileData['commission_rate'] ?? 10);
        $totalCommission = $totalRevenue * ($commissionRate / 100);

        $orderIds = OrderItem::where('seller_id', $sellerId)->pluck('order_id')->unique();
        $totalOrders = $orderIds->count();

        $thisMonthOrderIds = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', fn ($q) => $q->where('created_at', '>=', $startOfMonth))
            ->pluck('order_id')->unique();

        $lastMonthOrderIds = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', fn ($q) => $q->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth]))
            ->pluck('order_id')->unique();

        $statusCounts = OrderItem::where('seller_id', $sellerId)
            ->selectRaw('seller_status, COUNT(*) as count')
            ->whereNotNull('seller_status')
            ->groupBy('seller_status')
            ->pluck('count', 'seller_status')
            ->toArray();

        $productsBase = Product::where('seller_id', $sellerId);

        $productsTotal    = (clone $productsBase)->count();
        $productsActive   = (clone $productsBase)->where('is_active', true)->count();
        $productsDrafts   = (clone $productsBase)->where('is_active', false)->count();
        $productsArchived = (clone $productsBase)->onlyTrashed()->count();
        $productsOutOfStock = (clone $productsBase)->where('stock', '<=', 0)->count();
        $productsLowStock = (clone $productsBase)->where('stock', '>', 0)->whereColumn('stock', '<=', 'min_stock')->count();

        $productsViews = (clone $productsBase)->sum('views_count');
        $productsSales = (clone $productsBase)->sum('sales_count');

        $reviewsBase = Review::where('seller_id', $sellerId);

        $reviewsTotal     = (clone $reviewsBase)->count();
        $reviewsPending   = (clone $reviewsBase)->where('status', 'pending')->count();
        $reviewsApproved  = (clone $reviewsBase)->where('status', 'approved')->count();
        $reviewsRejected  = (clone $reviewsBase)->where('status', 'rejected')->count();
        $reviewsWithPhotos = (clone $reviewsBase)->whereHas('images')->count();
        $averageRating = (clone $reviewsBase)->where('status', 'approved')->avg('rating') ?? 0;

        $balanceAvailable = (float) ($profileData['balance'] ?? 0);
        $balancePending   = (float) ($profileData['pending_balance'] ?? 0);

        return [
            'revenue' => [
                'total'            => (float) $totalRevenue,
                'this_month'       => (float) $thisMonthRevenue,
                'last_month'       => (float) $lastMonthRevenue,
                'growth_percent'   => round($growthPercent, 2),
                'total_commission' => (float) $totalCommission,
            ],
            'orders' => [
                'total'        => $totalOrders,
                'this_month'   => $thisMonthOrderIds->count(),
                'last_month'   => $lastMonthOrderIds->count(),
                'to_accept'    => (int) ($statusCounts['to_accept'] ?? 0),
                'to_prepare'   => (int) ($statusCounts['preparing'] ?? 0),
                'to_ship'      => (int) ($statusCounts['ready_to_ship'] ?? 0),
                'shipped'      => (int) ($statusCounts['shipped'] ?? 0),
                'delivered'    => (int) ($statusCounts['delivered'] ?? 0),
                'cancelled'    => (int) ($statusCounts['cancelled'] ?? 0) + (int) ($statusCounts['rejected'] ?? 0),
            ],
            'products' => [
                'total'         => $productsTotal,
                'active'        => $productsActive,
                'drafts'        => $productsDrafts,
                'archived'      => $productsArchived,
                'out_of_stock'  => $productsOutOfStock,
                'low_stock'     => $productsLowStock,
                'total_views'   => (int) $productsViews,
                'total_sales'   => (int) $productsSales,
            ],
            'reviews' => [
                'average_rating' => round((float) $averageRating, 2),
                'total'          => $reviewsTotal,
                'pending'        => $reviewsPending,
                'approved'       => $reviewsApproved,
                'rejected'       => $reviewsRejected,
                'with_photos'    => $reviewsWithPhotos,
            ],
            'balance' => [
                'available'          => $balanceAvailable,
                'pending'            => $balancePending,
                'can_request_payout' => $balanceAvailable > 0,
                'payout_method'      => $profileData['payout_method'] ?? null,
            ],
        ];
    }

    private function buildAlerts(int $sellerId, array $profileData): array
    {
        $alerts = [];
        $kycStatus = $profileData['kyc_status'] ?? 'pending';

        if ($kycStatus === 'pending') {
            $alerts[] = ['type' => 'kyc_pending', 'level' => 'warning', 'message' => 'Votre KYC est en attente de validation.', 'count' => 1, 'action' => 'profile'];
        }

        if ($kycStatus === 'rejected') {
            $alerts[] = ['type' => 'kyc_rejected', 'level' => 'danger', 'message' => 'Votre KYC a été refusé. Veuillez le soumettre à nouveau.', 'count' => 1, 'action' => 'profile'];
        }

        $toAccept = OrderItem::where('seller_id', $sellerId)->where('seller_status', 'to_accept')->count();
        if ($toAccept > 0) {
            $alerts[] = ['type' => 'orders_to_accept', 'level' => 'warning', 'message' => $toAccept === 1 ? '1 commande en attente d\'acceptation.' : "$toAccept commandes en attente d'acceptation.", 'count' => $toAccept, 'action' => 'orders'];
        }

        $toShip = OrderItem::where('seller_id', $sellerId)->where('seller_status', 'preparing')->count();
        if ($toShip > 0) {
            $alerts[] = ['type' => 'orders_to_ship', 'level' => 'warning', 'message' => $toShip === 1 ? '1 commande à expédier.' : "$toShip commandes à expédier.", 'count' => $toShip, 'action' => 'orders'];
        }

        $outOfStock = Product::where('seller_id', $sellerId)->where('stock', '<=', 0)->count();
        if ($outOfStock > 0) {
            $alerts[] = ['type' => 'stock_out', 'level' => 'danger', 'message' => $outOfStock === 1 ? '1 produit en rupture de stock.' : "$outOfStock produits en rupture de stock.", 'count' => $outOfStock, 'action' => 'products'];
        }

        $lowStock = Product::where('seller_id', $sellerId)->where('stock', '>', 0)->whereColumn('stock', '<=', 'min_stock')->count();
        if ($lowStock > 0) {
            $alerts[] = ['type' => 'stock_low', 'level' => 'warning', 'message' => $lowStock === 1 ? '1 produit en stock faible.' : "$lowStock produits en stock faible.", 'count' => $lowStock, 'action' => 'products'];
        }

        $pendingReviews = Review::where('seller_id', $sellerId)->where('status', 'pending')->count();
        if ($pendingReviews > 0) {
            $alerts[] = ['type' => 'reviews_pending', 'level' => 'info', 'message' => $pendingReviews === 1 ? '1 avis en attente de modération.' : "$pendingReviews avis en attente de modération.", 'count' => $pendingReviews, 'action' => 'reviews'];
        }

        return $alerts;
    }

    private function fetchRecentOrdersArray(int $sellerId, int $limit = 5): array
    {
        $orders = Order::query()
            ->whereHas('items', fn ($q) => $q->where('seller_id', $sellerId))
            ->with(['user:id,name,email', 'items' => fn ($q) => $q->where('seller_id', $sellerId)])
            ->latest('created_at')
            ->limit($limit)
            ->get();

        return $orders->map(function ($order) {
            $sellerTotal = $order->items->sum('total_price');
            return [
                'id'               => $order->id,
                'order_number'     => $order->order_number,
                'status'           => $order->status,
                'status_label'     => $order->status_label,
                'payment_status'   => $order->payment_status,
                'customer_name'    => $order->user?->full_name ?? 'Client',
                'items_count'      => $order->items->count(),
                'total'            => (float) $sellerTotal,
                'total_formatted'  => number_format((float) $sellerTotal, 0, ',', ' ') . ' FCFA',
                'created_at'       => $order->created_at?->toIso8601String(),
                'created_at_human' => $order->created_at?->diffForHumans(),
            ];
        })->toArray();
    }

    private function fetchTopProductsArray(int $sellerId, int $limit = 5): array
    {
        $products = Product::where('seller_id', $sellerId)
            ->orderByDesc('sales_count')
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();

        return $products->map(function ($product) {
            return [
                'id'              => $product->id,
                'name'            => $product->name,
                'slug'            => $product->slug,
                'main_image_url'  => $product->main_image_url,
                'price'           => (float) $product->price,
                'price_formatted' => number_format((float) $product->price, 0, ',', ' ') . ' FCFA',
                'sales_count'     => (int) $product->sales_count,
                'views_count'     => (int) $product->views_count,
                'average_rating'  => (float) $product->average_rating,
                'stock'           => (int) $product->stock,
                'is_active'       => (bool) $product->is_active,
            ];
        })->toArray();
    }

    private function buildSalesChart(int $sellerId, int $period): array
    {
        $startDate = now()->subDays($period - 1)->startOfDay();
        $endDate   = now()->endOfDay();

        $salesByDay = OrderItem::query()
            ->where('seller_id', $sellerId)
            ->whereIn('item_status', ['delivered', 'shipped'])
            ->whereHas('order', fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->selectRaw('DATE(created_at) as date, SUM(total_price) as revenue, COUNT(*) as items')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chartData = [];
        $totalRevenue = 0;
        $totalItems = 0;

        for ($i = 0; $i < $period; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dateStr = $date->toDateString();
            $dayData = $salesByDay->get($dateStr);

            $revenue = (float) ($dayData->revenue ?? 0);
            $items   = (int) ($dayData->items ?? 0);

            $chartData[] = [
                'date'              => $dateStr,
                'date_formatted'    => $date->format('d/m'),
                'day_name'          => ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'][$date->dayOfWeek],
                'revenue'           => $revenue,
                'revenue_formatted' => number_format($revenue, 0, ',', ' ') . ' FCFA',
                'items'             => $items,
            ];

            $totalRevenue += $revenue;
            $totalItems   += $items;
        }

        return [
            'period'         => $period,
            'period_label'   => match ($period) {
                7   => '7 derniers jours',
                30  => '30 derniers jours',
                90  => '90 derniers jours',
                default => "$period jours",
            },
            'total_revenue'  => $totalRevenue,
            'total_revenue_formatted' => number_format($totalRevenue, 0, ',', ' ') . ' FCFA',
            'total_items'    => $totalItems,
            'average_daily_revenue' => $period > 0 ? round($totalRevenue / $period, 2) : 0,
            'data'           => $chartData,
        ];
    }

    private function resolveSellerId(Request $request): int
    {
        $user = $request->user();
        if ($user->isAdmin() && $request->has('seller_id')) {
            $sellerId = (int) $request->input('seller_id');
            if (!SellerProfile::where('user_id', $sellerId)->exists()) {
                abort(Response::HTTP_NOT_FOUND, 'Vendeur introuvable.');
            }
            return $sellerId;
        }
        return $user->id;
    }

    private function validatePeriod($period): int
    {
        $period = (int) $period;
        return in_array($period, [7, 30, 90], true) ? $period : 30;
    }

    private function cacheKey(string $type, int $sellerId): string
    {
        return self::CACHE_PREFIX . ":{$sellerId}:{$type}";
    }

    private function success(string $message, $data = null, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    private function error(string $message, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}