<?php

namespace App\Observers;

use App\Models\Catalog\Stock;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Observer : StockObserver
 *
 * Déclenche les alertes de stock bas / rupture au vendeur.
 *
 * Règles métier :
 *   1. Stock disponible <= 0          → Alerte RUPTURE (critical)
 *   2. Stock disponible <= min_quantity → Alerte STOCK BAS (warning)
 *   3. Stock disponible > min_quantity  → Alerte RÉSOLUE (reset)
 *
 * Anti-spam (throttle) :
 *   Chaque alerte est throttlée à 1 par tranche de 30 minutes par stock.
 *   → Empêche le vendeur de recevoir 50 notifications si 50 commandes
 *     vident le stock en 5 minutes.
 *
 * Idempotence :
 *   - On n'envoie l'alerte que si le stock traverse le seuil (pas s'il
 *     est déjà en dessous).
 *   - On utilise une clé de cache pour tracker l'état précédent.
 *
 * Queue (ShouldQueue) :
 *   Les notifications sont mises en queue pour ne PAS bloquer la requête
 *   de création de commande / ajustement stock. Le vendeur reçoit la
 *   notification dans les secondes suivantes, sans latence visible.
 *
 * @package App\Observers
 */
class StockObserver
{
    /**
     * Clé de cache pour tracker l'état précédent du stock.
     * Format : stock:last_alert:{stock_id}
     */
    private const CACHE_KEY_PREFIX = 'stock:last_alert:';

    /**
     * Durée du throttle (en secondes) : 30 minutes.
     */
    private const THROTTLE_SECONDS = 1800;

    /**
     * Après la mise à jour d'un stock.
     *
     * @param Stock $stock
     * @return void
     */
    public function updated(Stock $stock): void
    {
        // Rien à faire si available_quantity n'a pas changé
        if (!$stock->wasChanged('available_quantity') && !$stock->wasChanged('quantity')) {
            return;
        }

        // Rien à faire si le stock n'est plus actif
        if (!$stock->is_active) {
            return;
        }

        // Rien à faire si le vendeur n'existe plus
        $seller = User::find($stock->seller_id);
        if (!$seller || !$seller->isSeller()) {
            return;
        }

        try {
            $this->checkAndNotify($stock, $seller);
        } catch (\Throwable $e) {
            // Fail-safe : on ne casse jamais la requête pour une notif
            Log::warning('StockObserver - notification failed', [
                'stock_id' => $stock->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    // ============================================
    // LOGIQUE PRINCIPALE
    // ============================================

    /**
     * Vérifie l'état du stock et envoie la notification appropriée.
     */
    private function checkAndNotify(Stock $stock, User $seller): void
    {
        $available = (int) $stock->available_quantity;
        $min = (int) $stock->min_quantity;

        // Déterminer l'état actuel
        $currentState = $this->getStockState($available, $min);
        $previousState = $this->getPreviousState($stock->id);

        // Sauvegarder le nouvel état en cache
        $this->saveState($stock->id, $currentState);

        // Aucun changement de niveau → pas de notif
        if ($currentState === $previousState) {
            return;
        }

        // Passage de "OK" vers "Alerte" → envoyer notif
        if ($previousState === 'ok' && $currentState !== 'ok') {
            $this->sendLowStockNotification($stock, $seller, $currentState);
            return;
        }

        // Passage "Rupture" → "Stock bas" (réapprovisionnement partiel)
        if ($previousState === 'out' && $currentState === 'low') {
            $this->sendLowStockNotification($stock, $seller, 'low');
            return;
        }

        // Passage "Alerte" → "OK" (réapprovisionnement)
        if (in_array($previousState, ['low', 'out'], true) && $currentState === 'ok') {
            $this->sendStockResolvedNotification($stock, $seller);
        }
    }

    // ============================================
    // NOTIFICATIONS
    // ============================================

    /**
     * Envoie la notification d'alerte stock bas / rupture.
     */
    private function sendLowStockNotification(Stock $stock, User $seller, string $state): void
    {
        // Vérifier le throttle
        if ($this->isThrottled($stock->id, $state)) {
            Log::info('StockObserver - notification throttled', [
                'stock_id' => $stock->id,
                'state'    => $state,
            ]);
            return;
        }

        // Charger le produit pour le message
        $product = $stock->product;
        if (!$product) {
            return;
        }

        $message = $state === 'out'
            ? "⚠️ Rupture de stock : {$product->name}"
            : "🟡 Stock bas : {$product->name}";

        $details = $state === 'out'
            ? "Il ne reste plus aucune unité disponible. Réapprovisionnez rapidement pour éviter les ventes perdues."
            : "Il ne reste que {$stock->available_quantity} unité(s) en stock (seuil : {$stock->min_quantity}).";

        Log::info('StockObserver - sending low stock alert', [
            'stock_id'   => $stock->id,
            'seller_id'  => $seller->id,
            'product_id' => $product->id,
            'state'      => $state,
            'available'  => $stock->available_quantity,
        ]);

        // Envoyer la notification en queue
        try {
            Notification::send($seller, new \App\Notifications\LowStockNotification(
                product: $product,
                stock: $stock,
                state: $state,
                title: $message,
                message: $details,
            ));
        } catch (\Throwable $e) {
            Log::warning('StockObserver - failed to send notification', [
                'stock_id' => $stock->id,
                'error'    => $e->getMessage(),
            ]);
        }

        // Marquer le throttle
        $this->markThrottle($stock->id, $state);
    }

    /**
     * Envoie la notification de résolution (stock revenu à la normale).
     */
    private function sendStockResolvedNotification(Stock $stock, User $seller): void
    {
        $product = $stock->product;
        if (!$product) {
            return;
        }

        Log::info('StockObserver - sending resolved alert', [
            'stock_id'  => $stock->id,
            'seller_id' => $seller->id,
            'product_id'=> $product->id,
            'available' => $stock->available_quantity,
        ]);

        try {
            Notification::send($seller, new \App\Notifications\LowStockNotification(
                product: $product,
                stock: $stock,
                state: 'resolved',
                title: "✅ Stock revenu à la normale : {$product->name}",
                message: "Le stock est désormais à {$stock->available_quantity} unité(s).",
            ));
        } catch (\Throwable $e) {
            Log::warning('StockObserver - failed to send resolved notification', [
                'stock_id' => $stock->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    // ============================================
    // ÉTAT & CACHE
    // ============================================

    /**
     * Détermine l'état du stock selon la quantité disponible.
     */
    private function getStockState(int $available, int $min): string
    {
        if ($available <= 0) return 'out';
        if ($available <= $min) return 'low';
        return 'ok';
    }

    /**
     * Récupère l'état précédent depuis le cache.
     */
    private function getPreviousState(int $stockId): string
    {
        return Cache::get(
            self::CACHE_KEY_PREFIX . $stockId,
            'ok' // Par défaut : considéré comme "ok" (pas d'alerte)
        );
    }

    /**
     * Sauvegarde l'état actuel en cache.
     */
    private function saveState(int $stockId, string $state): void
    {
        Cache::put(
            self::CACHE_KEY_PREFIX . $stockId,
            $state,
            now()->addDays(7) // Expiration : 7 jours (ménage automatique)
        );
    }

    // ============================================
    // THROTTLE
    // ============================================

    /**
     * Vérifie si l'alerte est throttlée pour ce stock et cet état.
     */
    private function isThrottled(int $stockId, string $state): bool
    {
        return Cache::has($this->throttleKey($stockId, $state));
    }

    /**
     * Marque l'alerte comme envoyée (pour throttle).
     */
    private function markThrottle(int $stockId, string $state): void
    {
        Cache::put(
            $this->throttleKey($stockId, $state),
            true,
            now()->addSeconds(self::THROTTLE_SECONDS)
        );
    }

    /**
     * Génère la clé de throttle.
     */
    private function throttleKey(int $stockId, string $state): string
    {
        return "stock:throttle:{$stockId}:{$state}";
    }
}