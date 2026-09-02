<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Catalog\Product;
use App\Models\Catalog\Category;
use App\Models\Catalog\Brand;
use App\Models\Catalog\Stock;
use App\Models\Order\Order;
use App\Models\Review\Review;
use App\Models\Payment\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Récupérer les statistiques générales du dashboard administrateur.
     *
     * Endpoint : GET /api/v1/admin/dashboard/stats
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        try {
            // ============================================
            // STATISTIQUES GÉNÉRALES
            // ============================================
            $totalRevenue = Order::where('payment_status', 'paid')->sum('total');
            $totalOrders = Order::count();
            $totalUsers = User::count();
            $totalProducts = Product::count();
            $totalCategories = Category::count();
            $totalBrands = Brand::count();

            // Panier moyen
            $averageOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

            // Taux de conversion (commandes / visites)
            $totalVisits = DB::table('admin_logs')->where('action', 'visit')->count();
            $conversionRate = $totalVisits > 0 ? round(($totalOrders / $totalVisits) * 100, 2) : 0;

            // ============================================
            // DONNÉES DE GRAPHIQUES
            // ============================================

            // Ventes par jour (30 derniers jours)
            $salesByDay = Order::where('created_at', '>=', now()->subDays(30))
                ->where('payment_status', 'paid')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(total) as revenue'),
                    DB::raw('COUNT(*) as orders_count')
                )
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'revenue' => (float) $item->revenue,
                        'orders' => (int) $item->orders_count,
                    ];
                })
                ->toArray();

            // Commandes par statut
            $ordersByStatus = Order::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Revenus par mois (12 derniers mois)
            $revenueByMonth = Order::where('created_at', '>=', now()->subMonths(12))
                ->where('payment_status', 'paid')
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('SUM(total) as revenue'),
                    DB::raw('COUNT(*) as orders_count')
                )
                ->groupBy('month')
                ->orderBy('month', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => $item->month,
                        'revenue' => (float) $item->revenue,
                        'orders' => (int) $item->orders_count,
                    ];
                })
                ->toArray();

            // Commandes par heure de la journée
            $ordersByHour = Order::select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as orders_count')
            )
                ->groupBy('hour')
                ->orderBy('hour', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'hour' => (int) $item->hour,
                        'orders' => (int) $item->orders_count,
                    ];
                })
                ->toArray();

            // Commandes par jour de la semaine
            $ordersByDayOfWeek = Order::select(
                DB::raw('DAYOFWEEK(created_at) as day_of_week'),
                DB::raw('COUNT(*) as orders_count')
            )
                ->groupBy('day_of_week')
                ->orderBy('day_of_week', 'asc')
                ->get()
                ->map(function ($item) {
                    $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                    return [
                        'day' => $days[$item->day_of_week - 1] ?? 'Jour ' . $item->day_of_week,
                        'orders' => (int) $item->orders_count,
                    ];
                })
                ->toArray();

            // ============================================
            // TOP ÉLÉMENTS
            // ============================================

            // Top 10 produits par revenu
            $topProducts = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->select(
                    'products.id',
                    'products.name',
                    DB::raw('SUM(order_items.quantity) as quantity'),
                    DB::raw('SUM(order_items.total_price) as revenue')
                )
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('revenue')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => (int) $item->quantity,
                        'revenue' => (float) $item->revenue,
                    ];
                })
                ->toArray();

            // Top 10 catégories par revenu
            $topCategories = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->select(
                    'categories.id',
                    'categories.name',
                    DB::raw('SUM(order_items.total_price) as revenue'),
                    DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count')
                )
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('revenue')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'revenue' => (float) $item->revenue,
                        'orders' => (int) $item->orders_count,
                    ];
                })
                ->toArray();

            // Top 10 marques par revenu
            $topBrands = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('brands', 'products.brand_id', '=', 'brands.id')
                ->select(
                    'brands.id',
                    'brands.name',
                    DB::raw('SUM(order_items.total_price) as revenue'),
                    DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count')
                )
                ->groupBy('brands.id', 'brands.name')
                ->orderByDesc('revenue')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'revenue' => (float) $item->revenue,
                        'orders' => (int) $item->orders_count,
                    ];
                })
                ->toArray();

            // ============================================
            // DONNÉES RÉCENTES
            // ============================================

            // 10 dernières commandes
            $recentOrders = Order::with('user')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer' => $order->user ? $order->user->name : 'Client',
                        'total' => (float) $order->total,
                        'status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'created_at' => $order->created_at->toIso8601String(),
                    ];
                })
                ->toArray();

            // 10 derniers utilisateurs
            $recentUsers = User::orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->created_at->toIso8601String(),
                    ];
                })
                ->toArray();

            // ============================================
            // ALERTES
            // ============================================

            // Produits en stock faible
            $lowStockProducts = Stock::whereRaw('quantity <= min_quantity')
                ->with('product')
                ->get()
                ->map(function ($stock) {
                    return [
                        'id' => $stock->id,
                        'name' => $stock->product ? $stock->product->name : 'Produit',
                        'stock' => (int) $stock->quantity,
                        'min_stock' => (int) $stock->min_quantity,
                        'reserved' => (int) $stock->reserved_quantity,
                        'available' => (int) $stock->available_quantity,
                    ];
                })
                ->toArray();

            $pendingReviewsCount = Review::where('status', 'pending')->count();
            $pendingOrdersCount = Order::where('status', 'pending')->count();
            $pendingPaymentsCount = Payment::where('status', 'pending')->count();
            $pendingReturnsCount = Order::where('status', 'returned')->count();

            // ============================================
            // PERFORMANCE
            // ============================================

            $totalFavorites = DB::table('favorites')->count();
            $totalReviews = Review::count();
            $averageRating = Review::avg('rating') ?? 0;

            return response()->json([
                'success' => true,
                'message' => 'Statistiques du dashboard récupérées avec succès',
                'data' => [
                    // Statistiques générales
                    'total_revenue' => (float) $totalRevenue,
                    'total_orders' => (int) $totalOrders,
                    'total_users' => (int) $totalUsers,
                    'total_products' => (int) $totalProducts,
                    'total_categories' => (int) $totalCategories,
                    'total_brands' => (int) $totalBrands,
                    'average_order_value' => (float) $averageOrderValue,
                    'conversion_rate' => (float) $conversionRate,

                    // Graphiques
                    'sales_by_day' => $salesByDay,
                    'orders_by_status' => $ordersByStatus,
                    'revenue_by_month' => $revenueByMonth,
                    'orders_by_hour' => $ordersByHour,
                    'orders_by_day_of_week' => $ordersByDayOfWeek,

                    // Top éléments
                    'top_products' => $topProducts,
                    'top_categories' => $topCategories,
                    'top_brands' => $topBrands,

                    // Données récentes
                    'recent_orders' => $recentOrders,
                    'recent_users' => $recentUsers,

                    // Alertes
                    'low_stock_count' => count($lowStockProducts),
                    'low_stock_products' => $lowStockProducts,
                    'pending_reviews_count' => (int) $pendingReviewsCount,
                    'pending_orders_count' => (int) $pendingOrdersCount,
                    'pending_payments_count' => (int) $pendingPaymentsCount,
                    'pending_returns_count' => (int) $pendingReturnsCount,

                    // Performance
                    'total_visits' => (int) $totalVisits,
                    'total_favorites' => (int) $totalFavorites,
                    'total_reviews' => (int) $totalReviews,
                    'average_rating' => (float) $averageRating,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques du dashboard',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les statistiques pour une période donnée.
     *
     * Endpoint : GET /api/v1/admin/dashboard/stats/period
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function statsByPeriod(Request $request)
    {
        try {
            $startDate = $request->query('start_date', now()->subDays(30)->toDateString());
            $endDate = $request->query('end_date', now()->toDateString());

            $totalRevenue = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->sum('total');

            $totalOrders = Order::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->count();
            $totalUsers = User::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->count();
            $totalProducts = Product::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->count();

            $salesByDay = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(total) as revenue'),
                    DB::raw('COUNT(*) as orders_count')
                )
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'revenue' => (float) $item->revenue,
                        'orders' => (int) $item->orders_count,
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Statistiques par période récupérées avec succès',
                'data' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_revenue' => (float) $totalRevenue,
                    'total_orders' => (int) $totalOrders,
                    'total_users' => (int) $totalUsers,
                    'total_products' => (int) $totalProducts,
                    'sales_by_day' => $salesByDay,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques par période',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les ventes par jour.
     *
     * Endpoint : GET /api/v1/admin/dashboard/sales-by-day
     */
    public function salesByDay()
    {
        try {
            $sales = Order::where('payment_status', 'paid')
                ->where('created_at', '>=', now()->subDays(30))
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(total) as revenue'),
                    DB::raw('COUNT(*) as orders_count')
                )
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'revenue' => (float) $item->revenue,
                        'orders' => (int) $item->orders_count,
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Ventes par jour récupérées',
                'data' => $sales,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des ventes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les revenus par mois.
     *
     * Endpoint : GET /api/v1/admin/dashboard/revenue-by-month
     */
    public function revenueByMonth()
    {
        try {
            $revenue = Order::where('payment_status', 'paid')
                ->where('created_at', '>=', now()->subMonths(12))
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('SUM(total) as revenue'),
                    DB::raw('COUNT(*) as orders_count')
                )
                ->groupBy('month')
                ->orderBy('month', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => $item->month,
                        'revenue' => (float) $item->revenue,
                        'orders' => (int) $item->orders_count,
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Revenus par mois récupérés',
                'data' => $revenue,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des revenus mensuels',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les commandes par statut.
     *
     * Endpoint : GET /api/v1/admin/dashboard/orders-by-status
     */
    public function ordersByStatus()
    {
        try {
            $orders = Order::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Commandes par statut récupérées',
                'data' => $orders,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes par statut',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les commandes par heure.
     *
     * Endpoint : GET /api/v1/admin/dashboard/orders-by-hour
     */
    public function ordersByHour()
    {
        try {
            $orders = Order::select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as orders_count')
            )
                ->groupBy('hour')
                ->orderBy('hour', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'hour' => (int) $item->hour,
                        'orders' => (int) $item->orders_count,
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Commandes par heure récupérées',
                'data' => $orders,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes par heure',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les commandes par jour de la semaine.
     *
     * Endpoint : GET /api/v1/admin/dashboard/orders-by-day-of-week
     */
    public function ordersByDayOfWeek()
    {
        try {
            $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
            
            $orders = Order::select(
                DB::raw('DAYOFWEEK(created_at) as day_of_week'),
                DB::raw('COUNT(*) as orders_count')
            )
                ->groupBy('day_of_week')
                ->orderBy('day_of_week', 'asc')
                ->get()
                ->map(function ($item) use ($days) {
                    return [
                        'day' => $days[$item->day_of_week - 1] ?? 'Jour ' . $item->day_of_week,
                        'orders' => (int) $item->orders_count,
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Commandes par jour de la semaine récupérées',
                'data' => $orders,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes par jour',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les top produits.
     *
     * Endpoint : GET /api/v1/admin/dashboard/top-products
     */
    public function topProducts()
    {
        try {
            $products = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->select(
                    'products.id',
                    'products.name',
                    DB::raw('SUM(order_items.quantity) as quantity'),
                    DB::raw('SUM(order_items.total_price) as revenue')
                )
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('revenue')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => (int) $item->quantity,
                        'revenue' => (float) $item->revenue,
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Top produits récupérés',
                'data' => $products,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des top produits',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les top catégories.
     *
     * Endpoint : GET /api/v1/admin/dashboard/top-categories
     */
    public function topCategories()
    {
        try {
            $categories = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->select(
                    'categories.id',
                    'categories.name',
                    DB::raw('SUM(order_items.total_price) as revenue'),
                    DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count')
                )
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('revenue')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'revenue' => (float) $item->revenue,
                        'orders' => (int) $item->orders_count,
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Top catégories récupérées',
                'data' => $categories,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des top catégories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les top marques.
     *
     * Endpoint : GET /api/v1/admin/dashboard/top-brands
     */
    public function topBrands()
    {
        try {
            $brands = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('brands', 'products.brand_id', '=', 'brands.id')
                ->select(
                    'brands.id',
                    'brands.name',
                    DB::raw('SUM(order_items.total_price) as revenue'),
                    DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count')
                )
                ->groupBy('brands.id', 'brands.name')
                ->orderByDesc('revenue')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'revenue' => (float) $item->revenue,
                        'orders' => (int) $item->orders_count,
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Top marques récupérées',
                'data' => $brands,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des top marques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les commandes récentes.
     *
     * Endpoint : GET /api/v1/admin/dashboard/recent-orders
     */
    public function recentOrders(Request $request)
    {
        try {
            $limit = $request->query('limit', 10);

            $orders = Order::with('user')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer' => $order->user ? $order->user->name : 'Client',
                        'total' => (float) $order->total,
                        'status' => $order->status,
                        'created_at' => $order->created_at->toIso8601String(),
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Commandes récentes récupérées',
                'data' => $orders,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes récentes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les utilisateurs récents.
     *
     * Endpoint : GET /api/v1/admin/dashboard/recent-users
     */
    public function recentUsers(Request $request)
    {
        try {
            $limit = $request->query('limit', 10);

            $users = User::orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->created_at->toIso8601String(),
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateurs récents récupérés',
                'data' => $users,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs récents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les produits en stock faible.
     *
     * Endpoint : GET /api/v1/admin/dashboard/low-stock-products
     */
    public function lowStockProducts()
    {
        try {
            $products = Stock::whereRaw('quantity <= min_quantity')
                ->with('product')
                ->get()
                ->map(function ($stock) {
                    return [
                        'id' => $stock->id,
                        'name' => $stock->product ? $stock->product->name : 'Produit',
                        'stock' => (int) $stock->quantity,
                        'min_stock' => (int) $stock->min_quantity,
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Produits en stock faible récupérés',
                'data' => $products,
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
     * Récupérer le nombre d'avis en attente.
     *
     * Endpoint : GET /api/v1/admin/dashboard/pending-reviews-count
     */
    public function pendingReviewsCount()
    {
        try {
            $count = Review::where('status', 'pending')->count();

            return response()->json([
                'success' => true,
                'message' => 'Nombre d\'avis en attente récupéré',
                'data' => ['count' => (int) $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du nombre d\'avis en attente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer le nombre de commandes en attente.
     *
     * Endpoint : GET /api/v1/admin/dashboard/pending-orders-count
     */
    public function pendingOrdersCount()
    {
        try {
            $count = Order::where('status', 'pending')->count();

            return response()->json([
                'success' => true,
                'message' => 'Nombre de commandes en attente récupéré',
                'data' => ['count' => (int) $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du nombre de commandes en attente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer le nombre de paiements en attente.
     *
     * Endpoint : GET /api/v1/admin/dashboard/pending-payments-count
     */
    public function pendingPaymentsCount()
    {
        try {
            $count = Payment::where('status', 'pending')->count();

            return response()->json([
                'success' => true,
                'message' => 'Nombre de paiements en attente récupéré',
                'data' => ['count' => (int) $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du nombre de paiements en attente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer le nombre de retours en attente.
     *
     * Endpoint : GET /api/v1/admin/dashboard/pending-returns-count
     */
    public function pendingReturnsCount()
    {
        try {
            $count = Order::where('status', 'returned')->count();

            return response()->json([
                'success' => true,
                'message' => 'Nombre de retours en attente récupéré',
                'data' => ['count' => (int) $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du nombre de retours en attente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Générer un rapport de ventes.
     *
     * Endpoint : GET /api/v1/admin/dashboard/sales-report
     */
    public function salesReport(Request $request)
    {
        try {
            $startDate = $request->query('start_date', now()->subDays(30)->toDateString());
            $endDate = $request->query('end_date', now()->toDateString());

            $totalRevenue = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->sum('total');

            $totalOrders = Order::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->count();
            $paidOrders = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->count();
            $pendingOrders = Order::where('status', 'pending')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->count();
            $cancelledOrders = Order::where('status', 'cancelled')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->count();
            $deliveredOrders = Order::where('status', 'delivered')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->count();

            $averageOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;
            $totalCustomers = User::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->count();
            $totalItemsSold = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereBetween('orders.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->sum('order_items.quantity');

            return response()->json([
                'success' => true,
                'message' => 'Rapport de ventes généré avec succès',
                'data' => [
                    'period' => 'custom',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'generated_at' => now()->toIso8601String(),
                    'total_revenue' => (float) $totalRevenue,
                    'total_orders' => (int) $totalOrders,
                    'paid_orders' => (int) $paidOrders,
                    'pending_orders' => (int) $pendingOrders,
                    'cancelled_orders' => (int) $cancelledOrders,
                    'delivered_orders' => (int) $deliveredOrders,
                    'average_order_value' => (float) $averageOrderValue,
                    'total_customers' => (int) $totalCustomers,
                    'total_items_sold' => (int) $totalItemsSold,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport de ventes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les statistiques de performance.
     *
     * Endpoint : GET /api/v1/admin/dashboard/performance
     */
    public function performance()
    {
        try {
            $totalVisits = DB::table('admin_logs')->where('action', 'visit')->count();
            $totalFavorites = DB::table('favorites')->count();
            $totalReviews = Review::count();
            $averageRating = Review::avg('rating') ?? 0;
            $totalOrders = Order::count();
            $conversionRate = $totalVisits > 0 ? round(($totalOrders / $totalVisits) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'message' => 'Statistiques de performance récupérées',
                'data' => [
                    'total_visits' => (int) $totalVisits,
                    'total_favorites' => (int) $totalFavorites,
                    'total_reviews' => (int) $totalReviews,
                    'average_rating' => (float) $averageRating,
                    'conversion_rate' => (float) $conversionRate,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques de performance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}