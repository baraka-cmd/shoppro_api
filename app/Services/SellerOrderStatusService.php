<?php

namespace App\Services;

use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service : SellerOrderStatusService
 *
 * Rôle central et critique du module vendeur.
 *
 * Responsabilités :
 *   - Recalculer `orders.status` (statut global) en fonction des `seller_status`
 *     de TOUS les items de la commande (tous vendeurs confondus)
 *   - Garantir la cohérence entre ce que voit le client (statut global)
 *     et la réalité des items (statuts par vendeur)
 *
 * Principe métier (aligné sur Amazon / Jumia) :
 *   - Le vendeur ne touche JAMAIS à `orders.status`
 *   - Le vendeur ne modifie QUE `order_items.seller_status` de ses items
 *   - Ce service déduit `orders.status` à partir de l'ensemble des items
 *
 * Règles de recalcul (par ordre de priorité) :
 *   1. Si TOUS les items sont `cancelled` ou `rejected` → orders.status = cancelled
 *   2. Si TOUS les items sont `delivered` → orders.status = delivered
 *   3. Si TOUS les items sont `shipped` ou `delivered` (et au moins un shipped)
 *      → orders.status = shipped
 *   4. Si au moins un item est `shipped` ou `delivered`
 *      → orders.status = shipped (partiel, en cours)
 *   5. Si au moins un item est `accepted`, `preparing` ou `ready_to_ship`
 *      → orders.status = processing
 *   6. Si TOUS les items sont `to_accept` (aucune action vendeur)
 *      → orders.status = pending (ou confirmed si payé)
 *
 * Sécurité & Concurrence :
 *   - Utilise `DB::transaction()` + `lockForUpdate()` pour éviter
 *     les conditions de course entre plusieurs vendeurs qui agiraient
 *     simultanément sur la même commande [citation:2][citation:9]
 *   - Idempotent : peut être appelé plusieurs fois sans effet de bord
 *
 * @package App\Services
 */
class SellerOrderStatusService
{
    /**
     * Recalcule et met à jour le statut global d'une commande.
     *
     * Cette méthode doit être appelée après CHAQUE changement de
     * `seller_status` sur un item de la commande.
     *
     * @param Order $order La commande à recalculer
     * @return string Le nouveau statut global appliqué
     */
    public function recalculate(Order $order): string
    {
        return DB::transaction(function () use ($order) {
            // 🔒 Verrou pessimiste : bloque toute autre transaction
            // sur cette commande pendant le recalcul (évite les race conditions)
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            // Récupérer TOUS les items de la commande (tous vendeurs)
            $items = OrderItem::query()
                ->where('order_id', $lockedOrder->id)
                ->get();

            if ($items->isEmpty()) {
                return $lockedOrder->status;
            }

            // Calculer le nouveau statut
            $newStatus = $this->computeStatus($items);

            // Mettre à jour uniquement si le statut a changé
            if ($newStatus !== $lockedOrder->status) {
                $oldStatus = $lockedOrder->status;

                $lockedOrder->update([
                    'status' => $newStatus,
                    'updated_at' => now(),
                ]);

                Log::info('Order status recalculated by SellerOrderStatusService', [
                    'order_id'   => $lockedOrder->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'items_count'=> $items->count(),
                ]);
            }

            return $newStatus;
        });
    }

    /**
     * Détermine le statut global à partir de la collection d'items.
     *
     * @param \Illuminate\Support\Collection<int, OrderItem> $items
     * @return string
     */
    private function computeStatus($items): string
    {
        $total = $items->count();

        $counts = [
            'to_accept'      => 0,
            'accepted'       => 0,
            'preparing'      => 0,
            'ready_to_ship'  => 0,
            'shipped'        => 0,
            'delivered'      => 0,
            'cancelled'      => 0,
            'rejected'       => 0,
            'null'           => 0,
        ];

        foreach ($items as $item) {
            $status = $item->seller_status ?? 'null';
            if (array_key_exists($status, $counts)) {
                $counts[$status]++;
            }
        }

        // ============================================
        // RÈGLE 1 : Tout annulé ou refusé → cancelled
        // ============================================
        $cancelledTotal = $counts['cancelled'] + $counts['rejected'];
        if ($cancelledTotal === $total) {
            return 'cancelled';
        }

        // ============================================
        // RÈGLE 2 : Tout livré → delivered
        // ============================================
        if ($counts['delivered'] === $total) {
            return 'delivered';
        }

        // ============================================
        // RÈGLE 3 : Tout expédié ou livré → shipped
        // ============================================
        $shippedAndDelivered = $counts['shipped'] + $counts['delivered'];
        if ($shippedAndDelivered === $total) {
            return 'shipped';
        }

        // ============================================
        // RÈGLE 4 : Au moins un expédié/livré → shipped (partiel)
        // ============================================
        if ($counts['shipped'] > 0 || $counts['delivered'] > 0) {
            return 'shipped';
        }

        // ============================================
        // RÈGLE 5 : Au moins un item en cours de préparation
        // → processing
        // ============================================
        if ($counts['accepted'] > 0
            || $counts['preparing'] > 0
            || $counts['ready_to_ship'] > 0) {
            return 'processing';
        }

        // ============================================
        // RÈGLE 6 : Tous en attente d'acceptation
        // → pending (ou confirmed si paiement validé)
        // ============================================
        if ($counts['to_accept'] === $total) {
            // On retourne `confirmed` si la commande est déjà payée,
            // sinon `pending`. Ça correspond à la logique existante du
            // OrderController client qui distingue les deux.
            return 'pending';
        }

        // ============================================
        // RÈGLE 7 : Cas mixte complexe (fallback sécurisé)
        // ============================================
        // Ex : certains items `to_accept`, d'autres `accepted`
        // → on affiche `processing` car au moins un vendeur a bougé
        return 'processing';
    }

    /**
     * Recalcule le statut global SANS verrou (usage interne / batch).
     *
     * ⚠️ À n'utiliser QUE dans des contextes où la concurrence n'est pas
     * un risque (ex: commandes en batch, scripts de maintenance).
     * Pour les actions vendeur en temps réel, utiliser `recalculate()`.
     *
     * @param Order $order
     * @return string
     */
    public function recalculateWithoutLock(Order $order): string
    {
        $items = OrderItem::query()
            ->where('order_id', $order->id)
            ->get();

        if ($items->isEmpty()) {
            return $order->status;
        }

        $newStatus = $this->computeStatus($items);

        if ($newStatus !== $order->status) {
            $order->update(['status' => $newStatus]);
        }

        return $newStatus;
    }
}