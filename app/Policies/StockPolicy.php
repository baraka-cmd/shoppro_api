<?php

namespace App\Policies;

use App\Models\Catalog\Stock;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy : StockPolicy
 *
 * Contrôle les autorisations sur les stocks.
 *
 * Matrice d'accès :
 *   ┌────────────────────┬──────────┬─────────────────────┬────────┐
 *   │ Action             │ Client   │ Vendeur             │ Admin  │
 *   ├────────────────────┼──────────┼─────────────────────┼────────┤
 *   │ viewAny            │ ❌       │ ⚠️ ses stocks       │ ✅ tout│
 *   │ view               │ ❌       │ ⚠️ ses stocks       │ ✅     │
 *   │ adjust             │ ❌       │ ⚠️ ses stocks       │ ✅     │
 *   │ reserve            │ ❌       │ ❌ (auto système)   │ ✅     │
 *   │ release            │ ❌       │ ❌ (auto système)   │ ✅     │
 *   │ movements          │ ❌       │ ⚠️ ses mouvements   │ ✅     │
 *   │ alerts             │ ❌       │ ⚠️ ses alertes      │ ✅     │
 *   └────────────────────┴──────────┴─────────────────────┴────────┘
 *
 * ⚠️ Règles métier critiques :
 *   - Un vendeur ne peut ajuster QUE son propre stock
 *   - Les réservations/libérations sont gérées par le système
 *     (via OrderObserver / CheckoutService) → pas d'accès manuel
 *   - Un stock avec seller_id = NULL est un stock "admin"
 *   - Le vendeur ne peut PAS voir les stocks des autres vendeurs
 */
class StockPolicy
{
    /**
     * Autorise la liste des stocks.
     *
     * - Client : ❌ (n'a pas accès à la gestion des stocks)
     * - Vendeur : ✅ (mais filtré par scope forSeller)
     * - Admin  : ✅
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSeller();
    }

    /**
     * Autorise la consultation d'un stock.
     */
    public function view(User $user, Stock $stock): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller()) {
            // Stock vendeur : uniquement le sien
            if ($stock->seller_id === $user->id) {
                return Response::allow();
            }
            return Response::denyAsNotFound();
        }

        return Response::denyAsNotFound();
    }

    /**
     * Autorise l'ajustement manuel du stock.
     *
     * Le vendeur peut ajuster SON stock (inventaire physique, perte, casse).
     * L'admin peut ajuster n'importe quel stock.
     */
    public function adjust(User $user, Stock $stock): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if (!$user->isSeller()) {
            return Response::deny('Action réservée aux vendeurs et administrateurs.');
        }

        if ($stock->seller_id !== $user->id) {
            return Response::deny('Vous n\'êtes pas autorisé à ajuster ce stock.');
        }

        // Vérifier que le compte vendeur est actif
        $profile = $user->sellerProfile;
        if (!$profile || !$profile->is_active) {
            return Response::deny('Votre compte vendeur est désactivé.');
        }

        return Response::allow();
    }

    /**
     * Autorise la réservation de stock.
     *
     * ⚠️ Réservé au système (checkout), pas aux vendeurs.
     * Le vendeur NE DOIT PAS pouvoir réserver/libérer manuellement.
     */
    public function reserve(User $user, Stock $stock): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Action réservée au système.');
    }

    /**
     * Autorise la libération de stock réservé.
     *
     * ⚠️ Réservé au système (annulation commande), pas aux vendeurs.
     */
    public function release(User $user, Stock $stock): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Action réservée au système.');
    }

    /**
     * Autorise la consultation des mouvements de stock.
     */
    public function movements(User $user, Stock $stock): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $stock->seller_id === $user->id) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    /**
     * Autorise la consultation des alertes (stock faible / rupture).
     */
    public function alerts(User $user): bool
    {
        return $user->isAdmin() || $user->isSeller();
    }

    /**
     * Autorise la suppression d'un stock.
     * Réservé à l'admin (le vendeur ne peut pas supprimer un stock,
     * il doit le mettre à 0).
     */
    public function delete(User $user, Stock $stock): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Seuls les administrateurs peuvent supprimer un stock.');
    }
}