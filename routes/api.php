<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Product\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\Banner\BannerController;
//use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminReviewController;
use App\Http\Controllers\Api\Admin\AdminStockController;
use App\Http\Controllers\Api\Admin\AdminPromotionController;
use App\Http\Controllers\Api\Admin\AdminSettingController;
use App\Http\Controllers\Api\Admin\AdminNotificationController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Support\FaqController;
use App\Http\Controllers\Api\Support\FaqCategoryController;
use App\Http\Controllers\Api\Support\SupportTicketController;
use App\Http\Controllers\Api\Support\TicketMessageController;
use App\Http\Controllers\Api\Support\ContactController;
use App\Http\Controllers\Api\Legal\LegalDocumentController;
use App\Http\Controllers\Api\Review\ReviewController;
use App\Http\Controllers\Api\Order\OrderController;
use App\Http\Controllers\Api\Address\AddressController;




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
        Route::get('/{id}/reviews', [ReviewController::class, 'index']);
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
    // Route::prefix('favorites')->group(function () {
    //     Route::get('/', [FavoriteController::class, 'index']);
    //     Route::post('/toggle/{productId}', [FavoriteController::class, 'toggle']);
    //     Route::delete('/{id}', [FavoriteController::class, 'remove']);
    // });
    
    // ============ AVIS (CLIENT) ============
    Route::prefix('reviews')->group(function () {
        Route::post('/', [ReviewController::class, 'store']);
        Route::put('/{id}', [ReviewController::class, 'update']);
        Route::delete('/{id}', [ReviewController::class, 'destroy']);
        Route::post('/{id}/vote', [ReviewController::class, 'vote']); // 👈 NOUVEAU
    });

    Route::prefix('addresses')->group(function () {
        Route::get('/', [AddressController::class, 'index']);
        Route::post('/', [AddressController::class, 'store']);
        Route::get('/{id}', [AddressController::class, 'show']);
        Route::put('/{id}', [AddressController::class, 'update']);
        Route::delete('/{id}', [AddressController::class, 'destroy']);
        Route::post('/{id}/set-default', [AddressController::class, 'setDefault']);
    });

    // ============ COMMANDES (CLIENT) ============
    Route::prefix('orders')->group(function () {
        // ============================================
        // LISTE ET CRÉATION
        // ============================================
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);

        // ============================================
        // DÉTAILS
        // ============================================
        Route::get('/{id}', [OrderController::class, 'show']);

        // ============================================
        // ACTIONS
        // ============================================
        Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
        Route::get('/{id}/track', [OrderController::class, 'track']);



        // ============================================
        // 🆕 FACTURE PDF
        // ============================================
        Route::get('/{id}/invoice', [OrderController::class, 'invoice']);
        Route::get('/by-number/{orderNumber}/invoice', [OrderController::class, 'invoiceByNumber']);
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
    Route::post('/', [AdminUserController::class, 'store']);
    Route::get('/recent', [AdminUserController::class, 'recent']);
    Route::get('/stats/count', [AdminUserController::class, 'statsCount']);
    Route::get('/stats/by-role', [AdminUserController::class, 'statsByRole']);
    Route::get('/by-email/{email}', [AdminUserController::class, 'showByEmail']);
    Route::get('/{id}', [AdminUserController::class, 'show']);
    Route::put('/{id}', [AdminUserController::class, 'update']);
    Route::delete('/{id}', [AdminUserController::class, 'destroy']);
    Route::post('/{id}/restore', [AdminUserController::class, 'restore']);
    Route::put('/{id}/role', [AdminUserController::class, 'changeRole']);
    Route::post('/{id}/activate', [AdminUserController::class, 'activate']);
    Route::post('/{id}/deactivate', [AdminUserController::class, 'deactivate']);

    // 🆕 NOUVELLES ROUTES AVATAR
    Route::post('/{id}/avatar', [AdminUserController::class, 'uploadAvatar']);
    Route::delete('/{id}/avatar', [AdminUserController::class, 'deleteAvatar']);
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

    // ============ COMMANDES (ADMIN) ============
Route::prefix('orders')->group(function () {
    // ============================================
    // LISTE ET FILTRES
    // ============================================
    Route::get('/', [AdminOrderController::class, 'index']);
    Route::get('/pending', [AdminOrderController::class, 'pending']);
    Route::get('/confirmed', [AdminOrderController::class, 'confirmed']);
    Route::get('/processing', [AdminOrderController::class, 'processing']);
    Route::get('/shipped', [AdminOrderController::class, 'shipped']);
    Route::get('/delivered', [AdminOrderController::class, 'delivered']);
    Route::get('/cancelled', [AdminOrderController::class, 'cancelled']);

    // ============================================
    // RECHERCHE ET FILTRES SPÉCIAUX
    // ============================================
    Route::get('/by-user/{userId}', [AdminOrderController::class, 'byUser']);
    Route::get('/by-number/{orderNumber}', [AdminOrderController::class, 'showByNumber']);

    // ============================================
    // STATISTIQUES
    // ============================================
    Route::get('/stats/count', [AdminOrderController::class, 'statsCount']);
    Route::get('/stats/by-status', [AdminOrderController::class, 'statsByStatus']);
    Route::get('/stats/total-revenue', [AdminOrderController::class, 'statsTotalRevenue']);

    // ============================================
    // SOUS-RESSOURCES D'UNE COMMANDE
    // ============================================
    Route::get('/{id}/history', [AdminOrderController::class, 'history']);
    Route::get('/{id}/tracking', [AdminOrderController::class, 'tracking']);
    Route::get('/{id}/notes', [AdminOrderController::class, 'notes']);

    // 🆕 FACTURE PDF
    Route::get('/{id}/invoice', [AdminOrderController::class, 'invoice']);
    
    // ============================================
    // DÉTAILS
    // ============================================
    Route::get('/{id}', [AdminOrderController::class, 'show']);

    // ============================================
    // ACTIONS SUR LE STATUT
    // ============================================
    Route::post('/{id}/confirm', [AdminOrderController::class, 'confirm']);
    Route::post('/{id}/process', [AdminOrderController::class, 'process']);
    Route::post('/{id}/ship', [AdminOrderController::class, 'ship']);
    Route::post('/{id}/deliver', [AdminOrderController::class, 'deliver']);
    Route::post('/{id}/cancel', [AdminOrderController::class, 'cancel']);
    Route::post('/{id}/refund', [AdminOrderController::class, 'refund']);
    Route::post('/{id}/return', [AdminOrderController::class, 'returnOrder']);

    // ============================================
    // ACTIONS SUR LE PAIEMENT
    // ============================================
    Route::post('/{id}/payment/paid', [AdminOrderController::class, 'markPaymentAsPaid']);
    Route::post('/{id}/payment/failed', [AdminOrderController::class, 'markPaymentAsFailed']);
    Route::post('/{id}/payment/refunded', [AdminOrderController::class, 'markPaymentAsRefunded']);

    // ============================================
    // NOTES ADMIN
    // ============================================
    Route::post('/{id}/notes', [AdminOrderController::class, 'addNote']);

    // ============================================
    // SOFT DELETE
    // ============================================
    Route::post('/{id}/restore', [AdminOrderController::class, 'restore']);
    Route::delete('/{id}', [AdminOrderController::class, 'destroy']);
});

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
        Route::get('/stats/by-product', [AdminReviewController::class, 'statsByProduct']); // 🆕
        Route::get('/stats/average-rating', [AdminReviewController::class, 'statsAverageRating']);

        Route::get('/{id}', [AdminReviewController::class, 'show']);
        Route::delete('/{id}', [AdminReviewController::class, 'destroy']);

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






/*
|--------------------------------------------------------------------------
| API Routes - Support & Légal
|--------------------------------------------------------------------------
*/

// ============================================
// FAQ (PUBLIQUES)
// ============================================
Route::prefix('faqs')->group(function () {
    Route::get('/', [FaqController::class, 'index']);
    Route::get('/{id}', [FaqController::class, 'show']);
    Route::post('/{id}/helpful', [FaqController::class, 'markHelpful']);
});

// ============================================
// CATÉGORIES DE FAQ (PUBLIQUES)
// ============================================
Route::prefix('faq-categories')->group(function () {
    Route::get('/', [FaqCategoryController::class, 'index']);
    Route::get('/{id}', [FaqCategoryController::class, 'show']);
});

// ============================================
// CONTACT (PUBLIQUE AVEC RATE LIMITING)
// ============================================
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:10,1');

// ============================================
// DOCUMENTS LÉGAUX (PUBLIQUES)
// ============================================
Route::prefix('legal/documents')->group(function () {
    Route::get('/', [LegalDocumentController::class, 'index']);
    Route::get('/type/{type}', [LegalDocumentController::class, 'showByType']);
    Route::get('/{slug}', [LegalDocumentController::class, 'show']);
});

// ============================================
// TICKETS DE SUPPORT (AUTHENTIFIÉES)
// ============================================
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('support/tickets')->group(function () {
        Route::get('/', [SupportTicketController::class, 'index']);
        Route::post('/', [SupportTicketController::class, 'store']);
        Route::get('/{id}', [SupportTicketController::class, 'show']);
        Route::post('/{id}/close', [SupportTicketController::class, 'close']);
    });

    // Messages des tickets
    Route::prefix('support/tickets/{ticketId}/messages')->group(function () {
        Route::get('/', [TicketMessageController::class, 'index']);
        Route::post('/', [TicketMessageController::class, 'store']);
    });
});





// ============================================
// 🏪 PROFIL BOUTIQUE VENDEUR (KYC INDÉPENDANT)
// ============================================
// ⚠️ Ce groupe est séparé du reste du module seller pour
//    permettre à un vendeur KYC pending/rejected de :
//    - Voir son profil
//    - Modifier son profil
//    - Uploader logo/bannière
//    - Soumettre son KYC
//
// Middlewares :
//   - auth:sanctum       → authentification obligatoire
//   - role:seller,admin  → rôle vendeur ou admin
//   - PAS de seller.active → un vendeur pending doit pouvoir soumettre son KYC
// ============================================
Route::prefix('v1/seller/profile')
    ->middleware(['auth:sanctum', 'role:seller,admin'])
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Seller\SellerProfileController::class, 'show']);
        Route::put('/', [\App\Http\Controllers\Api\Seller\SellerProfileController::class, 'update']);
        Route::post('/logo', [\App\Http\Controllers\Api\Seller\SellerProfileController::class, 'uploadLogo']);
        Route::post('/banner', [\App\Http\Controllers\Api\Seller\SellerProfileController::class, 'uploadBanner']);
        Route::post('/submit-kyc', [\App\Http\Controllers\Api\Seller\SellerProfileController::class, 'submitKyc']);
    });

    
// ============================================
// 🏪 ROUTES VENDEUR (RÔLE VENDEUR OU ADMIN)
// ============================================
// Sécurité :
//   1. auth:sanctum        → authentification
//   2. role:seller,admin   → rôle obligatoire
//   3. seller.active       → KYC vérifié + compte actif
// ============================================
Route::prefix('v1/seller')
    ->middleware(['auth:sanctum', 'role:seller,admin', 'seller.active'])
    ->group(function () {

        // ============================================
        // 📊 DASHBOARD VENDEUR
        // ============================================
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Seller\SellerDashboardController::class, 'index']);
            Route::get('/sales-chart', [\App\Http\Controllers\Api\Seller\SellerDashboardController::class, 'salesChart']);
            Route::get('/recent-orders', [\App\Http\Controllers\Api\Seller\SellerDashboardController::class, 'recentOrders']);
            Route::get('/top-products', [\App\Http\Controllers\Api\Seller\SellerDashboardController::class, 'topProducts']);
            Route::get('/alerts', [\App\Http\Controllers\Api\Seller\SellerDashboardController::class, 'alerts']);
        });

        // ============================================
        // 📦 PRODUITS VENDEUR
        // ============================================
        Route::prefix('products')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Seller\SellerProductController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\Seller\SellerProductController::class, 'store']);
            Route::get('/{id}', [\App\Http\Controllers\Api\Seller\SellerProductController::class, 'show'])
                ->middleware('ownership:product');
            Route::put('/{id}', [\App\Http\Controllers\Api\Seller\SellerProductController::class, 'update'])
                ->middleware('ownership:product');
            Route::delete('/{id}', [\App\Http\Controllers\Api\Seller\SellerProductController::class, 'destroy'])
                ->middleware('ownership:product');
            Route::post('/{id}/publish', [\App\Http\Controllers\Api\Seller\SellerProductController::class, 'publish'])
                ->middleware('ownership:product');
            Route::post('/{id}/unpublish', [\App\Http\Controllers\Api\Seller\SellerProductController::class, 'unpublish'])
                ->middleware('ownership:product');
            Route::post('/{id}/duplicate', [\App\Http\Controllers\Api\Seller\SellerProductController::class, 'duplicate'])
                ->middleware('ownership:product');
            Route::get('/{id}/preview', [\App\Http\Controllers\Api\Seller\SellerProductController::class, 'preview'])
                ->middleware('ownership:product');
            Route::post('/{id}/images', [\App\Http\Controllers\Api\Seller\SellerProductController::class, 'uploadImages'])
                ->middleware('ownership:product');
            Route::delete('/{id}/images/{imageId}', [\App\Http\Controllers\Api\Seller\SellerProductController::class, 'deleteImage'])
                ->middleware('ownership:product');
            Route::post('/dismiss-onboarding', [SellerProfileController::class, 'dismissOnboarding']);
        });

        // ============================================
        // 🛒 COMMANDES VENDEUR
        // ============================================
        Route::prefix('orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Seller\SellerOrderController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\Api\Seller\SellerOrderController::class, 'show'])
                ->middleware('ownership:order');
            Route::post('/{id}/accept', [\App\Http\Controllers\Api\Seller\SellerOrderController::class, 'accept'])
                ->middleware('ownership:order');
            Route::post('/{id}/prepare', [\App\Http\Controllers\Api\Seller\SellerOrderController::class, 'prepare'])
                ->middleware('ownership:order');
            Route::post('/{id}/ship', [\App\Http\Controllers\Api\Seller\SellerOrderController::class, 'ship'])
                ->middleware('ownership:order');
            Route::post('/{id}/items/{itemId}/cancel', [\App\Http\Controllers\Api\Seller\SellerOrderController::class, 'cancelItem'])
                ->middleware('ownership:order');
        });

        // ============================================
        // ⭐ AVIS VENDEUR
        // ============================================
        Route::prefix('reviews')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Seller\SellerReviewController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\Api\Seller\SellerReviewController::class, 'show'])
                ->middleware('ownership:review');
            Route::post('/{id}/respond', [\App\Http\Controllers\Api\Seller\SellerReviewController::class, 'respond'])
                ->middleware('ownership:review');
            Route::put('/{id}/respond', [\App\Http\Controllers\Api\Seller\SellerReviewController::class, 'updateResponse'])
                ->middleware('ownership:review');
            Route::delete('/{id}/respond', [\App\Http\Controllers\Api\Seller\SellerReviewController::class, 'deleteResponse'])
                ->middleware('ownership:review');
        });

        // ============================================
        // 📦 STOCKS VENDEUR
        // ============================================
        Route::prefix('stocks')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Seller\SellerStockController::class, 'index']);
            Route::get('/low', [\App\Http\Controllers\Api\Seller\SellerStockController::class, 'lowStock']);
            Route::get('/out', [\App\Http\Controllers\Api\Seller\SellerStockController::class, 'outOfStock']);
            Route::get('/movements', [\App\Http\Controllers\Api\Seller\SellerStockController::class, 'movements']);
            Route::get('/{id}', [\App\Http\Controllers\Api\Seller\SellerStockController::class, 'show'])
                ->middleware('ownership:stock');
            Route::post('/{id}/adjust', [\App\Http\Controllers\Api\Seller\SellerStockController::class, 'adjust'])
                ->middleware('ownership:stock');
        });

        // ============================================
        // 📈 STATISTIQUES VENDEUR
        // ============================================
        Route::prefix('stats')->group(function () {
            Route::get('/sales', [\App\Http\Controllers\Api\Seller\SellerStatsController::class, 'sales']);
            Route::get('/products', [\App\Http\Controllers\Api\Seller\SellerStatsController::class, 'products']);
            Route::get('/customers', [\App\Http\Controllers\Api\Seller\SellerStatsController::class, 'customers']);
            Route::get('/export', [\App\Http\Controllers\Api\Seller\SellerStatsController::class, 'export']);
        });

        // ============================================
        // 💰 PAYOUTS (VERSEMENTS)
        // ============================================
        Route::prefix('payouts')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Seller\SellerPayoutController::class, 'index']);
            Route::get('/balance', [\App\Http\Controllers\Api\Seller\SellerPayoutController::class, 'balance']);
            Route::post('/request', [\App\Http\Controllers\Api\Seller\SellerPayoutController::class, 'request']);
            Route::get('/{id}', [\App\Http\Controllers\Api\Seller\SellerPayoutController::class, 'show'])
                ->middleware('ownership:payout');
            Route::post('/{id}/cancel', [\App\Http\Controllers\Api\Seller\SellerPayoutController::class, 'cancel'])
                ->middleware('ownership:payout');
        });

        

        // ============================================
        // 🔔 NOTIFICATIONS VENDEUR
        // ============================================
        Route::prefix('notifications')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Seller\SellerNotificationController::class, 'index']);
            Route::get('/unread-count', [\App\Http\Controllers\Api\Seller\SellerNotificationController::class, 'unreadCount']);
            Route::post('/mark-all-read', [\App\Http\Controllers\Api\Seller\SellerNotificationController::class, 'markAllAsRead']);
            Route::post('/{id}/read', [\App\Http\Controllers\Api\Seller\SellerNotificationController::class, 'markAsRead'])
                ->middleware('ownership:notification');
        });

    });