<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Product\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BrandController;

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
        // Liste paginée des produits
        Route::get('/', [ProductController::class, 'index']);
        
        // Produits en vedette
        Route::get('/featured', [ProductController::class, 'featured']);
        
        // Produits populaires
        Route::get('/popular', [ProductController::class, 'popular']);
        
        // Produits récents
        Route::get('/recent', [ProductController::class, 'recent']);
        
        // Produits en promotion
        Route::get('/on-sale', [ProductController::class, 'onSale']);
        
        // Recherche de produits
        Route::get('/search', [ProductController::class, 'search']);
        
        // Filtres avancés
        Route::get('/filter', [ProductController::class, 'filter']);
        
        // Détails d'un produit
        Route::get('/{id}', [ProductController::class, 'show']);
        
        // Produits par catégorie
        Route::get('/category/{categoryId}', [ProductController::class, 'byCategory']);
        
        // Produits par marque
        Route::get('/brand/{brandId}', [ProductController::class, 'byBrand']);
        
        // Produits similaires
        Route::get('/{id}/similar', [ProductController::class, 'similar']);
        
        // Avis d'un produit
        Route::get('/{id}/reviews', [ProductController::class, 'productReviews']);
    });
    
    // ============ CATÉGORIES (PUBLIC) ============
    Route::prefix('categories')->group(function () {
        // Liste des catégories
        Route::get('/', [CategoryController::class, 'index']);
        
        // Détails d'une catégorie
        Route::get('/{id}', [CategoryController::class, 'show']);
        
        // Produits d'une catégorie
        Route::get('/{id}/products', [CategoryController::class, 'products']);
        
        // Sous-catégories d'une catégorie
        Route::get('/{id}/subcategories', [CategoryController::class, 'subcategories']);
    });
    
    // ============ MARQUES (PUBLIC) ============
    Route::prefix('brands')->group(function () {
        // Liste des marques
        Route::get('/', [BrandController::class, 'index']);
        
        // Détails d'une marque
        Route::get('/{id}', [BrandController::class, 'show']);
        
        // Produits d'une marque
        Route::get('/{id}/products', [BrandController::class, 'products']);
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
        // Créer un produit
        Route::post('/', [ProductController::class, 'store']);
        
        // Mettre à jour un produit
        Route::put('/{id}', [ProductController::class, 'update']);
        
        // Supprimer un produit
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        
        // Restaurer un produit supprimé
        Route::post('/{id}/restore', [ProductController::class, 'restore']);
        
        // Upload d'images produit
        Route::post('/{id}/images', [ProductController::class, 'uploadImages']);
        
        // Supprimer une image
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
        // Liste des produits du vendeur
        Route::get('/', [ProductController::class, 'sellerProducts']);
        
        // Créer un produit
        Route::post('/', [ProductController::class, 'store']);
        
        // Mettre à jour
        Route::put('/{id}', [ProductController::class, 'update']);
        
        // Supprimer
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });
    
});