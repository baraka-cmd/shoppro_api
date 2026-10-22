<?php

namespace App\Policies;

use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy : OrderPolicy
 *
 * Contrôle les autorisations sur les commandes.
 *
 * ⚠️ Spécificité de `Order` :
 *   - Une commande peut contenir des items de PLUSIEURS vendeurs
 *   - `Order` n'a PAS de `seller_id` direct
 *   - Un vendeur ne voit QUE ses items dans la commande
 *
 * Matrice d'accès :
 *   ┌────────────────────┬───────────┬──────────────────┬────────┐
 *   │ Action             │ Client    │ Vendeur          │ Admin  │
 *   ├────────────────────┼───────────┼──────────────────┼────────┤
 *   │ viewAny            │ ❌        │ ⚠️ ses items     │ ✅ tout│
 *   │ view               │ ⚠️ siennes │ ⚠️ avec ses items│ ✅ tout│
 *   │ create             │ ✅        │ ❌               │ ✅     │
 *   │ cancel             │ ⚠️ siennes│ ⚠️ ses items     │ ✅     │
 *   │ updateStatus       │ ❌        │ ⚠️ ses items     │ ✅     │
 *   │ accept             │ ❌        │ ⚠️ ses items     │ ✅     │
 *   │ prepare            │ ❌        │ ⚠️ ses items     │ ✅     │
 *   │ ship               │ ❌        │ ⚠️ ses items     │ ✅     │
 *   │ cancelItem         │ ❌        │ ⚠️ ses items     │ ✅     │
 *   │ returnOrder        │ ⚠️ siennes│ ⚠️ ses items     │ ✅     │
 *   │ invoice            │ ⚠️ siennes│ ⚠️ ses items     │ ✅     │
 *   └────────────────────┴───────────┴──────────────────┴────────┘
 *
 * ⚠️ Règles métier critiques :
 *   - Un vendeur ne peut JAMAIS modifier le statut global d'une commande
 *     contenant aussi des items d'AUTRES vendeurs
 *   - Un vendeur peut uniquement changer le statut de SES items
 *   - Un client ne peut annuler que les commandes 'pending'/'confirmed'
 *   - L'admin a tous les droits
 *
 * @see \App\Models\Concerns\ScopesBySeller
 */
class OrderPolicy
{
    /**
     * Autorise la liste des commandes.
     *
     * - Client : ❌ (le client doit passer par /orders, pas /admin/orders)
     * - Vendeur : ✅ (mais sera filtré par scope seller)
     * - Admin  : ✅ (voit tout)
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSeller();
    }

    /**
     * Autorise la consultation d'une commande.
     *
     * - Client : ✅ UNIQUEMENT ses propres commandes
     * - Vendeur : ✅ SI la commande contient au moins 1 item de ce vendeur
     * - Admin  : ✅ tout
     */
    public function view(User $user, Order $order): Response
    {
        // Admin : peut tout voir
        if ($user->isAdmin()) {
            return Response::allow();
        }

        // Client : uniquement SES commandes
        if ($user->isClient()) {
            if ($order->user_id === $user->id) {
                return Response::allow();
            }
            return Response::denyAsNotFound();
        }

        // Vendeur : uniquement les commandes contenant SES items
        if ($user->isSeller()) {
            $hasSellerItems = $order->items()
                ->where('seller_id', $user->id)
                ->exists();

            if ($hasSellerItems) {
                return Response::allow();
            }

            return Response::denyAsNotFound();
        }

        return Response::denyAsNotFound();
    }

    /**
     * Autorise la création d'une commande.
     * Réservé aux clients connectés.
     */
    public function create(User $user): Response
    {
        if ($user->isClient() || $user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Seuls les clients peuvent passer une commande.');
    }

    /**
     * Autorise l'annulation d'une commande.
     *
     * - Client : ⚠️ ses commandes UNIQUEMENT si status = pending/confirmed
     * - Vendeur : ⚠️ impossible au niveau commande globale (utiliser cancelItem)
     * - Admin  : ✅ tout
     */
    public function cancel(User $user, Order $order): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isClient()) {
            if ($order->user_id !== $user->id) {
                return Response::denyAsNotFound();
            }

            // Le client ne peut annuler que si la commande est encore annulable
            if (!in_array($order->status, ['pending', 'confirmed'])) {
                return Response::deny('Cette commande ne peut plus être annulée.');
            }

            return Response::allow();
        }

        // Vendeur : ne peut PAS annuler une commande entière
        if ($user->isSeller()) {
            return Response::deny('Utilisez l\'annulation d\'article au lieu de l\'annulation de commande.');
        }

        return Response::deny('Action non autorisée.');
    }

    /**
     * Autorise le changement de statut global.
     * Réservé à l'admin.
     */
    public function updateStatus(User $user, Order $order): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Seuls les administrateurs peuvent modifier le statut global.');
    }

    /**
     * Autorise un vendeur à ACCEPTER SES items dans une commande.
     */
    public function accept(User $user, Order $order): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if (!$user->isSeller()) {
            return Response::deny('Action réservée aux vendeurs.');
        }

        $hasPendingItems = $order->items()
            ->where('seller_id', $user->id)
            ->where('seller_status', 'to_accept')
            ->exists();

        if (!$hasPendingItems) {
            return Response::deny('Aucun article à accepter dans cette commande.');
        }

        return Response::allow();
    }

    /**
     * Autorise un vendeur à PRÉPARER SES items.
     */
    public function prepare(User $user, Order $order): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if (!$user->isSeller()) {
            return Response::deny('Action réservée aux vendeurs.');
        }

        $hasAcceptedItems = $order->items()
            ->where('seller_id', $user->id)
            ->where('seller_status', 'accepted')
            ->exists();

        if (!$hasAcceptedItems) {
            return Response::deny('Aucun article accepté à préparer.');
        }

        return Response::allow();
    }

    /**
     * Autorise un vendeur à EXPÉDIER SES items.
     */
    public function ship(User $user, Order $order): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if (!$user->isSeller()) {
            return Response::deny('Action réservée aux vendeurs.');
        }

        $hasPreparingItems = $order->items()
            ->where('seller_id', $user->id)
            ->where('seller_status', 'preparing')
            ->exists();

        if (!$hasPreparingItems) {
            return Response::deny('Aucun article en préparation à expédier.');
        }

        return Response::allow();
    }

    /**
     * Autorise un vendeur à ANNULER UN item spécifique.
     */
    public function cancelItem(User $user, Order $order, OrderItem $item): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if (!$user->isSeller()) {
            return Response::deny('Action réservée aux vendeurs.');
        }

        // L'item doit appartenir à ce vendeur ET à cette commande
        if ($item->seller_id !== $user->id || $item->order_id !== $order->id) {
            return Response::deny('Vous n\'êtes pas autorisé à annuler cet article.');
        }

        // L'item doit être annulable
        if (!in_array($item->seller_status, ['to_accept', 'accepted', 'preparing'])) {
            return Response::deny('Cet article ne peut plus être annulé.');
        }

        return Response::allow();
    }

    /**
     * Autorise le retour d'une commande.
     */
    public function returnOrder(User $user, Order $order): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isClient()) {
            if ($order->user_id !== $user->id) {
                return Response::denyAsNotFound();
            }

            if ($order->status !== 'delivered') {
                return Response::deny('Seules les commandes livrées peuvent être retournées.');
            }

            return Response::allow();
        }

        if ($user->isSeller()) {
            // Un vendeur peut initier un retour de SES items
            $hasSellerItems = $order->items()
                ->where('seller_id', $user->id)
                ->exists();

            if ($hasSellerItems && $order->status === 'delivered') {
                return Response::allow();
            }

            return Response::deny('Action non autorisée.');
        }

        return Response::deny('Action non autorisée.');
    }

    /**
     * Autorise le téléchargement de la facture.
     */
    public function invoice(User $user, Order $order): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isClient() && $order->user_id === $user->id) {
            return Response::allow();
        }

        if ($user->isSeller()) {
            $hasSellerItems = $order->items()
                ->where('seller_id', $user->id)
                ->exists();

            if ($hasSellerItems) {
                return Response::allow();
            }
        }

        return Response::deny('Vous n\'êtes pas autorisé à accéder à cette facture.');
    }

    /**
     * Autorise l'accès au suivi de livraison.
     */
    public function track(User $user, Order $order): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isClient() && $order->user_id === $user->id) {
            return Response::allow();
        }

        if ($user->isSeller()) {
            $hasSellerItems = $order->items()
                ->where('seller_id', $user->id)
                ->exists();

            if ($hasSellerItems) {
                return Response::allow();
            }
        }

        return Response::denyAsNotFound();
    }
}