<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| Ce fichier est utilisé pour définir vos commandes Artisan basées sur des
| closures. Vous pouvez également utiliser le Scheduler pour planifier
| l'exécution périodique de commandes.
|
*/

// ============================================
// COMMANDE INSPIRE (par défaut Laravel)
// ============================================
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================
// SCHEDULER — TÂCHES PLANIFIÉES
// ============================================

// --------------------------------------------------
// PAYOUTS MENSUELS VENDEURS
// --------------------------------------------------
// Exécution : 1er de chaque mois à 2h du matin
// Protection contre les exécutions simultanées
// Un seul serveur exécute si multi-serveurs
// Logs ajoutés dans storage/logs/payouts.log
//
// ⚠️ Pour le développement/test, tu peux changer
//    `monthlyOn(1, '02:00')` par `dailyAt('02:00')`.
// --------------------------------------------------
Schedule::command('seller:generate-monthly-payouts')
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/payouts.log'));

// ============================================
// AUTRES TÂCHES À PLANIFIER (optionnel)
// ============================================

// --------------------------------------------------
// NETTOYAGE DES SESSIONS EXPIRÉES
// --------------------------------------------------
// Exécution : tous les jours à 3h du matin
// Supprime les anciens tokens Sanctum expirés
// --------------------------------------------------
Schedule::command('sanctum:prune-expired --hours=24')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer();

// --------------------------------------------------
// NETTOYAGE DES NOTIFICATIONS ANCIENNES
// --------------------------------------------------
// Exécution : tous les jours à 4h du matin
// Supprime les notifications lues de plus de 30 jours
// --------------------------------------------------
Schedule::call(function () {
    \Illuminate\Support\Facades\DB::table('notifications')
        ->whereNotNull('read_at')
        ->where('read_at', '<', now()->subDays(30))
        ->delete();
})->dailyAt('04:00')
  ->name('cleanup-old-notifications')
  ->withoutOverlapping();

// --------------------------------------------------
// RECALCUL DES STATS VENDEURS (optionnel)
// --------------------------------------------------
// Exécution : tous les jours à 5h du matin
// Recalcule les compteurs dénormalisés (products_count, etc.)
// --------------------------------------------------
Schedule::call(function () {
    \App\Models\Seller\SellerProfile::chunk(100, function ($profiles) {
        foreach ($profiles as $profile) {
            $profile->refreshStats();
        }
    });
})->dailyAt('05:00')
  ->name('refresh-seller-stats')
  ->withoutOverlapping()
  ->onOneServer();

// --------------------------------------------------
// ALERTES STOCK AUTOMATIQUES (optionnel)
// --------------------------------------------------
// Exécution : tous les jours à 8h du matin
// Vérifie les stocks bas et envoie des notifications
// --------------------------------------------------
Schedule::call(function () {
    $lowStocks = \App\Models\Catalog\Stock::where('is_active', true)
        ->where('available_quantity', '>', 0)
        ->whereColumn('available_quantity', '<=', 'min_quantity')
        ->with(['product', 'product.seller'])
        ->get();

    foreach ($lowStocks as $stock) {
        if ($stock->product && $stock->product->seller) {
            $stock->product->seller->notify(
                new \App\Notifications\LowStockNotification(
                    product: $stock->product,
                    stock: $stock,
                    state: 'low',
                    title: "⚠️ Stock bas : {$stock->product->name}",
                    message: "Il ne reste que {$stock->available_quantity} unité(s)."
                )
            );
        }
    }
})->dailyAt('08:00')
  ->name('daily-stock-alerts')
  ->withoutOverlapping()
  ->onOneServer();

// ============================================
// RÉSUMÉ DES COMMANDES PLANIFIÉES
// ============================================
//
// | Commande                          | Fréquence           | Rôle                          |
// |-----------------------------------|---------------------|-------------------------------|
// | seller:generate-monthly-payouts   | 1er du mois à 2h    | Crée les payouts en attente   |
// | sanctum:prune-expired             | Tous les jours 3h   | Nettoie les tokens expirés    |
// | cleanup-old-notifications         | Tous les jours 4h   | Nettoie les vieilles notifs   |
// | refresh-seller-stats              | Tous les jours 5h   | Recalcule les stats vendeur   |
// | daily-stock-alerts                | Tous les jours 8h   | Alertes stock bas             |
//
// ⚠️ Pour voir les tâches planifiées : php artisan schedule:list
// ⚠️ Pour tester manuellement    : php artisan schedule:run
// ============================================