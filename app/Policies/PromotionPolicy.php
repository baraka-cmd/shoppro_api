<?php

namespace App\Policies;

use App\Models\Marketing\Promotion;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy : PromotionPolicy
 *
 * Contrôle les autorisations sur les promotions.
 *
 * Matrice d'accès :
 *   ┌────────────────────┬──────────┬─────────────────────┬────────┐
 *   │ Action             │ Client   │ Vendeur             │ Admin  │
 *   ├────────────────────┼──────────┼─────────────────────┼────────┤
 *   │ viewAny            │ ✅ public │ ✅                  │ ✅     │
 *   │ view               │ ✅ public │ ✅                  │ ✅     │
 *   │ create             │ ❌       │ ⚠️ ses produits     │ ✅     │
 *   │ update             │ ❌       │ ⚠️ ses promos       │ ✅     │
 *   │ delete             │ ❌       │ ⚠️ ses promos       │ ✅     │
 *   │ activate           │ ❌       │ ⚠️ ses promos       │ ✅     │
 *   │ deactivate         │ ❌       │ ⚠️ ses promos       │ ✅     │
 *   │ feature            │ ❌       │ ❌                  │ ✅     │
 *   └────────────────────┴──────────┴─────────────────────┴────────┘
 *
 * ⚠️ Règles métier critiques :
 *   - Un vendeur ne peut créer QUE des promotions sur SES produits
 *   - Un vendeur ne peut PAS mettre en avant (feature) ses promotions
 *   - Une promotion avec seller_id = NULL est une promo globale admin
 *   - Un vendeur ne peut PAS toucher une promo globale
 */
class PromotionPolicy
{
    /**
     * Autorise la liste des promotions.
     * Les promos actives sont publiques (client peut les voir).
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Autorise la consultation d'une promotion.
     */
    public function view(?User $user, Promotion $promotion): Response
    {
        // Promo active : publique
        if ($promotion->is_active) {
            return Response::allow();
        }

        if (!$user) {
            return Response::denyAsNotFound();
        }

        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $promotion->seller_id === $user->id) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    /**
     * Autorise la création d'une promotion.
     */
    public function create(User $user): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if (!$user->isSeller()) {
            return Response::deny('Seuls les vendeurs et administrateurs peuvent créer des promotions.');
        }

        $profile = $user->sellerProfile;
        if (!$profile || !$profile->canPublishProducts()) {
            return Response::deny('Votre compte vendeur doit être validé pour créer une promotion.');
        }

        return Response::allow();
    }

    /**
     * Autorise la modification d'une promotion.
     */
    public function update(User $user, Promotion $promotion): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $promotion->seller_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à modifier cette promotion.');
    }

    /**
     * Autorise la suppression d'une promotion.
     */
    public function delete(User $user, Promotion $promotion): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $promotion->seller_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à supprimer cette promotion.');
    }

    /**
     * Autorise l'activation d'une promotion.
     */
    public function activate(User $user, Promotion $promotion): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $promotion->seller_id === $user->id) {
            if ($promotion->is_active) {
                return Response::deny('Cette promotion est déjà active.');
            }
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à activer cette promotion.');
    }

    /**
     * Autorise la désactivation d'une promotion.
     */
    public function deactivate(User $user, Promotion $promotion): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $promotion->seller_id === $user->id) {
            if (!$promotion->is_active) {
                return Response::deny('Cette promotion est déjà inactive.');
            }
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à désactiver cette promotion.');
    }

    /**
     * Autorise la mise en avant (feature).
     * Réservé à l'admin (le vendeur ne peut pas se mettre en avant).
     */
    public function feature(User $user, Promotion $promotion): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Seuls les administrateurs peuvent mettre une promotion en avant.');
    }

    /**
     * Autorise la restauration d'une promotion soft-deleted.
     */
    public function restore(User $user, Promotion $promotion): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $promotion->seller_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à restaurer cette promotion.');
    }

    /**
     * Autorise la suppression définitive.
     * Réservé à l'admin.
     */
    public function forceDelete(User $user, Promotion $promotion): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Seuls les administrateurs peuvent supprimer définitivement une promotion.');
    }
}