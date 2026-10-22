<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

// Models
use App\Models\User;
use App\Models\Catalog\Product;
use App\Models\Catalog\Stock;
use App\Models\Order\Order;
use App\Models\Marketing\Promotion;
use App\Models\Review\Review;
use App\Models\Seller\SellerPayout;

// Policies
use App\Policies\ProductPolicy;
use App\Policies\StockPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PromotionPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\PayoutPolicy;

// Observers
use App\Observers\UserObserver;

use App\Models\Order\OrderItem;
use App\Observers\OrderItemObserver;

use App\Observers\StockObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ============================================
        // POLICIES
        // ============================================
        Gate::policy(Product::class,       ProductPolicy::class);
        Gate::policy(Order::class,         OrderPolicy::class);
        Gate::policy(Review::class,        ReviewPolicy::class);
        Gate::policy(Stock::class,         StockPolicy::class);
        Gate::policy(Promotion::class,     PromotionPolicy::class);
        Gate::policy(SellerPayout::class,  PayoutPolicy::class);

        // ============================================
        // OBSERVERS
        // ============================================
        User::observe(UserObserver::class);

        // Dans la méthode boot(), à côté de User::observe(UserObserver::class)
        OrderItem::observe(OrderItemObserver::class);
        Stock::observe(StockObserver::class);
    }
}