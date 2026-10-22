<?php

namespace App\Observers;

use App\Models\Order\OrderItem;
use App\Services\SellerOrderStatusService;
use Illuminate\Support\Facades\Log;

/**
 * Observer : OrderItemObserver
 *
 * Déclenche automatiquement le recalcul du statut global
 * (`orders.status`) dès qu'un `seller_status` change sur un item.
 *
 * Ceinture et bretelles :
 *   - Le contrôleur SellerOrderController recalcule déjà explicitement
 *   - Cet observer garantit que MÊME SI un autre contrôleur/admin/script
 *     modifie seller_status sans passer par le contrôleur, le statut global
 *     reste cohérent.
 *
 * Idempotence :
 *   - Le service `recalculate()` est idempotent (no-op si statut inchangé)
 *   - Pas de risque de boucle infinie (le service ne touche pas aux items)
 *
 * Logging :
 *   - Chaque déclenchement est loggé pour audit
 *   - Aucune exception ne remonte (fail-safe)
 *
 * @package App\Observers
 */
class OrderItemObserver
{
    public function __construct(
        private readonly SellerOrderStatusService $statusService,
    ) {}

    /**
     * Après la mise à jour d'un item.
     *
     * On ne réagit QUE si `seller_status` a changé, pour éviter
     * les recalculs inutiles quand on met à jour d'autres champs
     * (note, quantité, etc.).
     */
    public function updated(OrderItem $item): void
    {
        // Rien à faire si seller_status n'a pas changé
        if (!$item->wasChanged('seller_status')) {
            return;
        }

        // Rien à faire si la commande n'existe plus (cas de suppression cascade)
        if (!$item->order) {
            return;
        }

        try {
            $oldStatus = $item->getOriginal('seller_status');
            $newStatus = $item->seller_status;

            Log::info('OrderItemObserver - seller_status changed', [
                'order_id'     => $item->order_id,
                'item_id'      => $item->id,
                'old_status'   => $oldStatus,
                'new_status'   => $newStatus,
                'seller_id'    => $item->seller_id,
            ]);

            // Recalculer le statut global de la commande
            $this->statusService->recalculate($item->order);
        } catch (\Throwable $e) {
            // Fail-safe : on ne casse pas la requête si le recalcul échoue
            Log::error('OrderItemObserver - recalc failed', [
                'order_id' => $item->order_id,
                'item_id'  => $item->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}