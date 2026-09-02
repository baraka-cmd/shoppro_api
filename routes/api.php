<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Product\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\Banner\BannerController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminReviewController;
use App\Http\Controllers\Api\Admin\AdminStockController;
use App\Http\Controllers\Api\Admin\AdminPromotionController;
use App\Http\Controllers\Api\Admin\AdminSettingController;
use App\Http\Controllers\Api\Admin\AdminNotificationController;
use App\Http\Controllers\Api\Admin\AdminUserController;

/*
|--------------------------------------------------------------------------
| API Routes - ShopPro
|--------------------------------------------------------------------------
*/

// ============================================
// 🔓 ROUTES PUBLIQUES (SANS AUTHENTIFICATION)
// ============================================
Route::prefix('v1')->group(function () {
    
    // ============ AUTHENTIFICATION ============
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    });
    
    // ============ PRODUITS (PUBLIC) ============
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/featured', [ProductController::class, 'featured']);
        Route::get('/popular', [ProductController::class, 'popular']);
        Route::get('/recent', [ProductController::class, 'recent']);
        Route::get('/on-sale', [ProductController::class, 'onSale']);
        Route::get('/search', [ProductController::class, 'search']);
        Route::get('/filter', [ProductController::class, 'filter']);
        Route::get('/category/{categoryId}', [ProductController::class, 'byCategory']);
        Route::get('/brand/{brandId}', [ProductController::class, 'byBrand']);
        Route::get('/{id}/similar', [ProductController::class, 'similar']);
        Route::get('/{id}/reviews', [ProductController::class, 'productReviews']);
        Route::get('/{id}', [ProductController::class, 'show']);
    });
    
    // ============ CATÉGORIES (PUBLIC) ============
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{id}/products', [CategoryController::class, 'products']);
        Route::get('/{id}/subcategories', [CategoryController::class, 'subcategories']);
        Route::get('/{id}', [CategoryController::class, 'show']);
    });
    
    // ============ MARQUES (PUBLIC) ============
    Route::prefix('brands')->group(function () {
        Route::get('/', [BrandController::class, 'index']);
        Route::get('/{id}/products', [BrandController::class, 'products']);
        Route::get('/{id}', [BrandController::class, 'show']);
    });

    // ============ BANNIÈRES (PUBLIC) ============
    Route::prefix('banners')->group(function () {
        Route::get('/', [BannerController::class, 'index']);
        Route::get('/{id}', [BannerController::class, 'show']);
    });
    
});

// ============================================
// 🔒 ROUTES PROTÉGÉES (AUTHENTIFICATION REQUISE)
// ============================================
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // ============ AUTHENTIFICATION ============
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/upload-avatar', [AuthController::class, 'uploadAvatar']);
    });
    
    // ============ FAVORIS (CLIENT) ============
    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::post('/toggle/{productId}', [FavoriteController::class, 'toggle']);
        Route::delete('/{id}', [FavoriteController::class, 'remove']);
    });
    
    // ============ AVIS (CLIENT) ============
    Route::prefix('reviews')->group(function () {
        Route::post('/', [ReviewController::class, 'store']);
        Route::put('/{id}', [ReviewController::class, 'update']);
        Route::delete('/{id}', [ReviewController::class, 'destroy']);
    });
    
});

// ============================================
// 👑 ROUTES ADMIN (RÔLE ADMIN REQUIS)
// ============================================
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    
    // ============ GESTION DES PRODUITS ============
    Route::prefix('products')->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::post('/{id}/restore', [ProductController::class, 'restore']);
        Route::post('/{id}/images', [ProductController::class, 'uploadImages']);
        Route::delete('/{id}/images/{imageId}', [ProductController::class, 'deleteImage']);
    });
    
    // ============ GESTION DES CATÉGORIES ============
    Route::prefix('categories')->group(function () {
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });
    
    // ============ GESTION DES MARQUES ============
    Route::prefix('brands')->group(function () {
        Route::post('/', [BrandController::class, 'store']);
        Route::put('/{id}', [BrandController::class, 'update']);
        Route::delete('/{id}', [BrandController::class, 'destroy']);
    });

// ============ UTILISATEURS ============
Route::prefix('users')->group(function () {
    Route::get('/', [AdminUserController::class, 'index']);
    Route::get('/recent', [AdminUserController::class, 'recent']);
    Route::get('/by-email/{email}', [AdminUserController::class, 'showByEmail']);
    Route::get('/stats/count', [AdminUserController::class, 'statsCount']);
    Route::get('/stats/by-role', [AdminUserController::class, 'statsByRole']);
    Route::get('/{id}', [AdminUserController::class, 'show']);
    Route::post('/', [AdminUserController::class, 'store']);
    Route::put('/{id}', [AdminUserController::class, 'update']);
    Route::put('/{id}/role', [AdminUserController::class, 'changeRole']);
    Route::post('/{id}/activate', [AdminUserController::class, 'activate']);
    Route::post('/{id}/deactivate', [AdminUserController::class, 'deactivate']);
    Route::delete('/{id}', [AdminUserController::class, 'destroy']);
});

    // ============ GESTION DES BANNIÈRES ============
    Route::prefix('banners')->group(function () {
        Route::post('/', [BannerController::class, 'store']);
        Route::put('/{id}', [BannerController::class, 'update']);
        Route::delete('/{id}', [BannerController::class, 'destroy']);
    });

    // ============ DASHBOARD ============
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats']);
        Route::get('/stats/period', [DashboardController::class, 'statsByPeriod']);
        Route::get('/sales-by-day', [DashboardController::class, 'salesByDay']);
        Route::get('/revenue-by-month', [DashboardController::class, 'revenueByMonth']);
        Route::get('/orders-by-status', [DashboardController::class, 'ordersByStatus']);
        Route::get('/orders-by-hour', [DashboardController::class, 'ordersByHour']);
        Route::get('/orders-by-day-of-week', [DashboardController::class, 'ordersByDayOfWeek']);
        Route::get('/top-products', [DashboardController::class, 'topProducts']);
        Route::get('/top-categories', [DashboardController::class, 'topCategories']);
        Route::get('/top-brands', [DashboardController::class, 'topBrands']);
        Route::get('/recent-orders', [DashboardController::class, 'recentOrders']);
        Route::get('/recent-users', [DashboardController::class, 'recentUsers']);
        Route::get('/low-stock-products', [DashboardController::class, 'lowStockProducts']);
        Route::get('/pending-reviews-count', [DashboardController::class, 'pendingReviewsCount']);
        Route::get('/pending-orders-count', [DashboardController::class, 'pendingOrdersCount']);
        Route::get('/pending-payments-count', [DashboardController::class, 'pendingPaymentsCount']);
        Route::get('/pending-returns-count', [DashboardController::class, 'pendingReturnsCount']);
        Route::get('/sales-report', [DashboardController::class, 'salesReport']);
        Route::get('/performance', [DashboardController::class, 'performance']);
    });

    // ============ COMMANDES ============
    Route::prefix('orders')->group(function () {
        Route::get('/', [AdminOrderController::class, 'index']);
        Route::get('/pending', [AdminOrderController::class, 'pending']);
        Route::get('/confirmed', [AdminOrderController::class, 'confirmed']);
        Route::get('/processing', [AdminOrderController::class, 'processing']);
        Route::get('/shipped', [AdminOrderController::class, 'shipped']);
        Route::get('/delivered', [AdminOrderController::class, 'delivered']);
        Route::get('/cancelled', [AdminOrderController::class, 'cancelled']);
        Route::get('/by-user/{userId}', [AdminOrderController::class, 'byUser']);
        Route::get('/by-number/{orderNumber}', [AdminOrderController::class, 'showByNumber']);
        Route::get('/stats/count', [AdminOrderController::class, 'statsCount']);
        Route::get('/stats/by-status', [AdminOrderController::class, 'statsByStatus']);
        Route::get('/stats/total-revenue', [AdminOrderController::class, 'statsTotalRevenue']);
        Route::get('/{id}/history', [AdminOrderController::class, 'history']);
        Route::get('/{id}/tracking', [AdminOrderController::class, 'tracking']);
        Route::get('/{id}/notes', [AdminOrderController::class, 'notes']);
        Route::get('/{id}', [AdminOrderController::class, 'show']);
        Route::post('/{id}/confirm', [AdminOrderController::class, 'confirm']);
        Route::post('/{id}/process', [AdminOrderController::class, 'process']);
        Route::post('/{id}/ship', [AdminOrderController::class, 'ship']);
        Route::post('/{id}/deliver', [AdminOrderController::class, 'deliver']);
        Route::post('/{id}/cancel', [AdminOrderController::class, 'cancel']);
        Route::post('/{id}/refund', [AdminOrderController::class, 'refund']);
        Route::post('/{id}/return', [AdminOrderController::class, 'returnOrder']);
        Route::post('/{id}/payment/paid', [AdminOrderController::class, 'markPaymentAsPaid']);
        Route::post('/{id}/payment/failed', [AdminOrderController::class, 'markPaymentAsFailed']);
        Route::post('/{id}/payment/refunded', [AdminOrderController::class, 'markPaymentAsRefunded']);
        Route::post('/{id}/notes', [AdminOrderController::class, 'addNote']);
        Route::post('/{id}/restore', [AdminOrderController::class, 'restore']);
        Route::delete('/{id}', [AdminOrderController::class, 'destroy']);
    });

    // ============ AVIS ============
    Route::prefix('reviews')->group(function () {
        Route::get('/', [AdminReviewController::class, 'index']);
        Route::get('/pending', [AdminReviewController::class, 'pending']);
        Route::get('/approved', [AdminReviewController::class, 'approved']);
        Route::get('/rejected', [AdminReviewController::class, 'rejected']);
        Route::get('/spam', [AdminReviewController::class, 'spam']);
        Route::get('/by-product/{productId}', [AdminReviewController::class, 'byProduct']);
        Route::get('/by-user/{userId}', [AdminReviewController::class, 'byUser']);
        Route::get('/stats/count', [AdminReviewController::class, 'statsCount']);
        Route::get('/stats/by-status', [AdminReviewController::class, 'statsByStatus']);
        Route::get('/stats/by-rating', [AdminReviewController::class, 'statsByRating']);
        Route::get('/stats/average-rating', [AdminReviewController::class, 'statsAverageRating']);
        Route::get('/{id}', [AdminReviewController::class, 'show']);
        Route::post('/{id}/approve', [AdminReviewController::class, 'approve']);
        Route::post('/{id}/reject', [AdminReviewController::class, 'reject']);
        Route::post('/{id}/mark-spam', [AdminReviewController::class, 'markAsSpam']);
        Route::post('/{id}/reset', [AdminReviewController::class, 'reset']);
        Route::post('/{id}/respond', [AdminReviewController::class, 'respond']);
        Route::put('/{id}/respond', [AdminReviewController::class, 'updateResponse']);
        Route::delete('/{id}/respond', [AdminReviewController::class, 'deleteResponse']);
        Route::post('/{id}/recommend', [AdminReviewController::class, 'recommend']);
        Route::delete('/{id}/recommend', [AdminReviewController::class, 'unrecommend']);
        Route::post('/{id}/restore', [AdminReviewController::class, 'restore']);
        Route::post('/bulk-approve', [AdminReviewController::class, 'bulkApprove']);
        Route::post('/bulk-reject', [AdminReviewController::class, 'bulkReject']);
        Route::post('/bulk-delete', [AdminReviewController::class, 'bulkDelete']);
        Route::delete('/{id}', [AdminReviewController::class, 'destroy']);
    });

    // ============ STOCKS ============
    Route::prefix('stocks')->group(function () {
        Route::get('/', [AdminStockController::class, 'index']);
        Route::get('/low', [AdminStockController::class, 'lowStock']);
        Route::get('/out', [AdminStockController::class, 'outOfStock']);
        Route::get('/movements', [AdminStockController::class, 'movements']);
        Route::get('/stats', [AdminStockController::class, 'stats']);
        Route::get('/{id}', [AdminStockController::class, 'show']);
        Route::post('/{id}/reserve', [AdminStockController::class, 'reserve']);
        Route::post('/{id}/release', [AdminStockController::class, 'release']);
        Route::post('/{id}/adjust', [AdminStockController::class, 'adjust']);
    });

    // ============ PROMOTIONS ============
    Route::prefix('promotions')->group(function () {
        Route::get('/', [AdminPromotionController::class, 'index']);
        Route::get('/active', [AdminPromotionController::class, 'active']);
        Route::get('/upcoming', [AdminPromotionController::class, 'upcoming']);
        Route::get('/expired', [AdminPromotionController::class, 'expired']);
        Route::get('/featured', [AdminPromotionController::class, 'featured']);
        Route::get('/expiring-soon', [AdminPromotionController::class, 'expiringSoon']);
        Route::get('/check-slug/{slug}', [AdminPromotionController::class, 'checkSlug']);
        Route::get('/stats/count', [AdminPromotionController::class, 'statsCount']);
        Route::get('/stats/by-status', [AdminPromotionController::class, 'statsByStatus']);
        Route::get('/by-slug/{slug}', [AdminPromotionController::class, 'showBySlug']);
        Route::get('/{id}/stats', [AdminPromotionController::class, 'promotionStats']);
        Route::get('/{id}', [AdminPromotionController::class, 'show']);
        Route::post('/', [AdminPromotionController::class, 'store']);
        Route::put('/{id}', [AdminPromotionController::class, 'update']);
        Route::post('/{id}/activate', [AdminPromotionController::class, 'activate']);
        Route::post('/{id}/deactivate', [AdminPromotionController::class, 'deactivate']);
        Route::post('/{id}/pause', [AdminPromotionController::class, 'pause']);
        Route::post('/{id}/resume', [AdminPromotionController::class, 'resume']);
        Route::post('/{id}/cancel', [AdminPromotionController::class, 'cancel']);
        Route::post('/{id}/feature', [AdminPromotionController::class, 'toggleFeatured']);
        Route::post('/{id}/restore', [AdminPromotionController::class, 'restore']);
        Route::delete('/{id}', [AdminPromotionController::class, 'destroy']);
    });

    // ============ PARAMÈTRES ============
    Route::prefix('settings')->group(function () {
        Route::get('/', [AdminSettingController::class, 'index']);
        Route::get('/grouped', [AdminSettingController::class, 'grouped']);
        Route::get('/public', [AdminSettingController::class, 'publicSettings']);
        Route::get('/check-key/{key}', [AdminSettingController::class, 'checkKey']);
        Route::get('/stats/count', [AdminSettingController::class, 'statsCount']);
        Route::get('/stats/by-group', [AdminSettingController::class, 'statsByGroup']);
        Route::get('/group/{group}', [AdminSettingController::class, 'byGroup']);
        Route::get('/{key}', [AdminSettingController::class, 'show']);
        Route::post('/', [AdminSettingController::class, 'store']);
        Route::put('/bulk-update', [AdminSettingController::class, 'bulkUpdate']);
        Route::put('/group/{group}', [AdminSettingController::class, 'updateGroup']);
        Route::put('/{key}', [AdminSettingController::class, 'update']);
        Route::post('/{key}/activate', [AdminSettingController::class, 'activate']);
        Route::post('/{key}/deactivate', [AdminSettingController::class, 'deactivate']);
        Route::post('/cache/refresh', [AdminSettingController::class, 'refreshCache']);
        Route::post('/cache/clear', [AdminSettingController::class, 'clearCache']);
        Route::delete('/{key}', [AdminSettingController::class, 'destroy']);
    });

    // ============ NOTIFICATIONS ============
    Route::prefix('notifications')->group(function () {
        Route::get('/', [AdminNotificationController::class, 'index']);
        Route::get('/unread', [AdminNotificationController::class, 'unread']);
        Route::get('/read', [AdminNotificationController::class, 'read']);
        Route::get('/archived', [AdminNotificationController::class, 'archived']);
        Route::get('/urgent', [AdminNotificationController::class, 'urgent']);
        Route::get('/high-priority', [AdminNotificationController::class, 'highPriority']);
        Route::get('/recent', [AdminNotificationController::class, 'recent']);
        Route::get('/by-type/{type}', [AdminNotificationController::class, 'byType']);
        Route::get('/stats/count', [AdminNotificationController::class, 'statsCount']);
        Route::get('/stats/unread-count', [AdminNotificationController::class, 'statsUnreadCount']);
        Route::get('/stats/by-type', [AdminNotificationController::class, 'statsByType']);
        Route::get('/stats/by-priority', [AdminNotificationController::class, 'statsByPriority']);
        Route::get('/{id}', [AdminNotificationController::class, 'show']);
        Route::post('/{id}/read', [AdminNotificationController::class, 'markAsRead']);
        Route::post('/{id}/unread', [AdminNotificationController::class, 'markAsUnread']);
        Route::post('/{id}/archive', [AdminNotificationController::class, 'archive']);
        Route::post('/{id}/unarchive', [AdminNotificationController::class, 'unarchive']);
        Route::post('/mark-all-read', [AdminNotificationController::class, 'markAllAsRead']);
        Route::post('/archive-all-read', [AdminNotificationController::class, 'archiveAllRead']);
        Route::post('/bulk-read', [AdminNotificationController::class, 'bulkMarkAsRead']);
        Route::post('/bulk-archive', [AdminNotificationController::class, 'bulkArchive']);
        Route::post('/bulk-delete', [AdminNotificationController::class, 'bulkDelete']);
        Route::delete('/delete-all-read', [AdminNotificationController::class, 'deleteAllRead']);
        Route::delete('/delete-all-archived', [AdminNotificationController::class, 'deleteAllArchived']);
        Route::delete('/clear-all', [AdminNotificationController::class, 'clearAll']);
        Route::delete('/{id}', [AdminNotificationController::class, 'destroy']);
    });

    // ============ STATISTIQUES ============
    Route::prefix('stats')->group(function () {
        Route::get('/products', [ProductController::class, 'stats']);
        Route::get('/categories', [CategoryController::class, 'stats']);
        Route::get('/brands', [BrandController::class, 'stats']);
    });
    
});

// ============================================
// 🏪 ROUTES VENDEUR (RÔLE VENDEUR OU ADMIN)
// ============================================
Route::prefix('v1/seller')->middleware(['auth:sanctum', 'role:seller,admin'])->group(function () {
    
    // ============ GESTION DES PRODUITS DU VENDEUR ============
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'sellerProducts']);
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });
    
});