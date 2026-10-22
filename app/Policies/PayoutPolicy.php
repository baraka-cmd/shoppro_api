<?php

namespace App\Policies;

use App\Models\Seller\SellerPayout;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy : PayoutPolicy
 *
 * Contrôle les autorisations sur les versements (retraits vendeur).
 *
 * Matrice d'accès :
 *   ┌────────────────────┬──────────┬─────────────────────┬────────┐
 *   │ Action             │ Client   │ Vendeur             │ Admin  │
 *   ├────────────────────┼──────────┼─────────────────────┼────────┤
 *   │ viewAny            │ ❌       │ ⚠️ ses payouts      │ ✅ tout│
 *   │ view               │ ❌       │ ⚠️ ses payouts      │ ✅     │
 *   │ request            │ ❌       │ ⚠️ si éligible      │ ✅     │
 *   │ cancel             │ ❌       │ ⚠️ si pending       │ ✅     │
 *   │ process            │ ❌       │ ❌                  │ ✅     │
 *   │ complete           │ ❌       │ ❌                  │ ✅     │
 *   │ fail               │ ❌       │ ❌                  │ ✅     │
 *   └────────────────────┴──────────┴─────────────────────┴────────┘
 *
 * ⚠️ Règles métier critiques :
 *   - Un vendeur doit avoir balance > 0 pour demander un payout
 *   - Un vendeur ne peut avoir qu'UN SEUL payout pending/processing
 *   - Le vendeur peut annuler un payout tant qu'il est 'pending'
 *   - Une fois 'processing', seul l'admin peut intervenir
 *   - Les actions process/complete/fail sont strictement admin
 */
class PayoutPolicy
{
    /**
     * Autorise la liste des payouts.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSeller();
    }

    /**
     * Autorise la consultation d'un payout.
     */
    public function view(User $user, SellerPayout $payout): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $payout->seller_id === $user->id) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    /**
     * Autorise la demande de payout.
     *
     * Conditions strictes :
     *   - Le vendeur doit avoir un profil KYC validé
     *   - Le profil doit être actif
     *   - balance > 0
     *   - Aucun payout pending/processing en cours
     */
    public function request(User $user): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if (!$user->isSeller()) {
            return Response::deny('Seuls les vendeurs peuvent demander un versement.');
        }

        $profile = $user->sellerProfile;

        if (!$profile) {
            return Response::deny('Profil vendeur introuvable.');
        }

        if (!$profile->isKycVerified()) {
            return Response::deny('Votre KYC doit être validé pour demander un versement.');
        }

        if (!$profile->is_active) {
            return Response::deny('Votre compte vendeur est désactivé.');
        }

        if ($profile->balance <= 0) {
            return Response::deny('Vous n\'avez aucun solde disponible.');
        }

        if (!$profile->canRequestPayout()) {
            return Response::deny('Vous avez déjà un versement en cours de traitement.');
        }

        return Response::allow();
    }

    /**
     * Autorise l'annulation d'un payout par le vendeur.
     * Possible uniquement si le payout est encore 'pending'.
     */
    public function cancel(User $user, SellerPayout $payout): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $payout->seller_id === $user->id) {
            if ($payout->status !== SellerPayout::STATUS_PENDING) {
                return Response::deny('Ce versement ne peut plus être annulé.');
            }
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à annuler ce versement.');
    }

    /**
     * Autorise le traitement d'un payout (passage pending → processing).
     * Réservé à l'admin.
     */
    public function process(User $user, SellerPayout $payout): Response
    {
        if (!$user->isAdmin()) {
            return Response::deny('Seuls les administrateurs peuvent traiter un versement.');
        }

        if ($payout->status !== SellerPayout::STATUS_PENDING) {
            return Response::deny('Ce versement a déjà été traité.');
        }

        return Response::allow();
    }

    /**
     * Autorise la complétion d'un payout (passage processing → completed).
     * Réservé à l'admin.
     */
    public function complete(User $user, SellerPayout $payout): Response
    {
        if (!$user->isAdmin()) {
            return Response::deny('Seuls les administrateurs peuvent valider un versement.');
        }

        if ($payout->status !== SellerPayout::STATUS_PROCESSING) {
            return Response::deny('Ce versement n\'est pas en cours de traitement.');
        }

        return Response::allow();
    }

    /**
     * Autorise le marquage en échec d'un payout.
     * Réservé à l'admin.
     */
    public function fail(User $user, SellerPayout $payout): Response
    {
        if (!$user->isAdmin()) {
            return Response::deny('Seuls les administrateurs peuvent marquer un versement en échec.');
        }

        if (in_array($payout->status, [
            SellerPayout::STATUS_COMPLETED,
            SellerPayout::STATUS_CANCELLED,
            SellerPayout::STATUS_FAILED,
        ])) {
            return Response::deny('Ce versement est déjà finalisé.');
        }

        return Response::allow();
    }

    /**
     * Autorise la suppression d'un payout.
     * Réservé à l'admin (un payout completed ne doit jamais être supprimé).
     */
    public function delete(User $user, SellerPayout $payout): Response
    {
        if (!$user->isAdmin()) {
            return Response::deny('Seuls les administrateurs peuvent supprimer un versement.');
        }

        if ($payout->status === SellerPayout::STATUS_COMPLETED) {
            return Response::deny('Un versement complété ne peut pas être supprimé (traçabilité).');
        }

        return Response::allow();
    }
}