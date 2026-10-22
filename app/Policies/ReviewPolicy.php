<?php

namespace App\Policies;

use App\Models\Review\Review;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy : ReviewPolicy
 *
 * Contrôle les autorisations sur les avis.
 *
 * Matrice d'accès :
 *   ┌────────────────────┬──────────┬─────────────────────┬────────┐
 *   │ Action             │ Client   │ Vendeur             │ Admin  │
 *   ├────────────────────┼──────────┼─────────────────────┼────────┤
 *   │ viewAny (public)   │ ✅       │ ✅                  │ ✅     │
 *   │ view               │ ✅       │ ✅                  │ ✅     │
 *   │ create             │ ✅ achat │ ❌                  │ ✅     │
 *   │ update             │ ⚠️ sien  │ ❌                  │ ✅     │
 *   │ delete             │ ⚠️ sien  │ ❌                  │ ✅     │
 *   │ respond            │ ❌       │ ⚠️ ses produits     │ ✅     │
 *   │ approve            │ ❌       │ ⚠️ ses produits     │ ✅     │
 *   │ reject             │ ❌       │ ⚠️ ses produits     │ ✅     │
 *   │ markAsSpam         │ ❌       │ ⚠️ ses produits     │ ✅     │
 *   │ recommend          │ ❌       │ ⚠️ ses produits     │ ✅     │
 *   │ voteHelpful        │ ✅       │ ❌                  │ ✅     │
 *   └────────────────────┴──────────┴─────────────────────┴────────┘
 *
 * ⚠️ Règles métier critiques :
 *   - Un client ne peut créer un avis QUE s'il a acheté le produit
 *   - Un vendeur ne peut modérer QUE les avis sur SES produits
 *   - Un vendeur ne peut PAS voter sur ses propres avis
 *   - Un client ne peut voter qu'UNE fois par avis (contrainte DB)
 */
class ReviewPolicy
{
    /**
     * Autorise la consultation publique des avis.
     * Les avis approuvés sont visibles par tous.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Autorise la consultation d'un avis.
     * Public pour les avis approuvés ; vendeur/admin pour les non-approuvés.
     */
    public function view(?User $user, Review $review): Response
    {
        // Avis approuvé : visible par tous
        if ($review->status === 'approved') {
            return Response::allow();
        }

        if (!$user) {
            return Response::denyAsNotFound();
        }

        // Admin : peut voir tous les avis (y compris pending/rejected)
        if ($user->isAdmin()) {
            return Response::allow();
        }

        // Vendeur : peut voir les avis sur ses produits (y compris pending)
        if ($user->isSeller() && $review->seller_id === $user->id) {
            return Response::allow();
        }

        // Auteur de l'avis : peut toujours voir son propre avis
        if ($review->user_id === $user->id) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    /**
     * Autorise la création d'un avis.
     *
     * Conditions strictes :
     *   - Client uniquement (pas vendeur, pas admin en tant que vendeur)
     *   - Doit avoir acheté le produit (is_verified_purchase = true)
     *   - Ne peut créer qu'UN SEUL avis par produit (contrainte DB)
     */
    public function create(User $user, int $productId): Response
    {
        if ($user->isSeller()) {
            return Response::deny('Les vendeurs ne peuvent pas laisser d\'avis sur les produits.');
        }

        // Vérifier l'achat vérifié
        $hasPurchased = \App\Models\Order\OrderItem::whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->whereIn('status', ['delivered', 'shipped']);
            })
            ->where('product_id', $productId)
            ->exists();

        if (!$hasPurchased) {
            return Response::deny('Vous devez avoir acheté ce produit pour laisser un avis.');
        }

        // Vérifier unicité de l'avis
        $hasReview = Review::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->exists();

        if ($hasReview) {
            return Response::deny('Vous avez déjà laissé un avis sur ce produit.');
        }

        return Response::allow();
    }

    /**
     * Autorise la modification d'un avis.
     *
     * - Client : UNIQUEMENT son propre avis
     * - Vendeur : ❌ (ne peut pas modifier l'avis d'un client)
     * - Admin  : ✅
     */
    public function update(User $user, Review $review): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($review->user_id === $user->id) {
            // Un client ne peut modifier que ses avis pending/approved
            if (in_array($review->status, ['pending', 'approved'])) {
                return Response::allow();
            }
            return Response::deny('Cet avis ne peut plus être modifié.');
        }

        return Response::deny('Vous n\'êtes pas autorisé à modifier cet avis.');
    }

    /**
     * Autorise la suppression d'un avis.
     *
     * - Client : UNIQUEMENT son propre avis
     * - Vendeur : ❌
     * - Admin  : ✅
     */
    public function delete(User $user, Review $review): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($review->user_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à supprimer cet avis.');
    }

    /**
     * Autorise la restauration d'un avis soft-deleted.
     * Réservé à l'admin.
     */
    public function restore(User $user, Review $review): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Seuls les administrateurs peuvent restaurer un avis.');
    }

    /**
     * Autorise la suppression définitive.
     * Réservé à l'admin.
     */
    public function forceDelete(User $user, Review $review): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Seuls les administrateurs peuvent supprimer définitivement un avis.');
    }

    /**
     * Autorise un vendeur (ou admin) à répondre à un avis.
     *
     * Conditions :
     *   - L'avis doit concerner un produit du vendeur (via seller_id)
     *   - L'avis doit être approuvé (on ne répond pas à un pending)
     *   - Pas déjà répondu (ou alors il faut update)
     */
    public function respond(User $user, Review $review): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if (!$user->isSeller()) {
            return Response::deny('Action réservée aux vendeurs et administrateurs.');
        }

        // L'avis doit appartenir à ce vendeur
        if ($review->seller_id !== $user->id) {
            return Response::deny('Vous ne pouvez répondre qu\'aux avis sur vos produits.');
        }

        // L'avis doit être approuvé
        if ($review->status !== 'approved') {
            return Response::deny('Vous ne pouvez répondre qu\'aux avis approuvés.');
        }

        return Response::allow();
    }

    /**
     * Autorise la mise à jour d'une réponse existante.
     */
    public function updateResponse(User $user, Review $review): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $review->seller_id === $user->id) {
            if (!$review->admin_response) {
                return Response::deny('Aucune réponse existante à modifier.');
            }
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à modifier cette réponse.');
    }

    /**
     * Autorise la suppression d'une réponse.
     */
    public function deleteResponse(User $user, Review $review): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $review->seller_id === $user->id) {
            if (!$review->admin_response) {
                return Response::deny('Aucune réponse à supprimer.');
            }
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à supprimer cette réponse.');
    }

    /**
     * Autorise l'approbation d'un avis.
     */
    public function approve(User $user, Review $review): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $review->seller_id === $user->id) {
            if ($review->status === 'approved') {
                return Response::deny('Cet avis est déjà approuvé.');
            }
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à approuver cet avis.');
    }

    /**
     * Autorise le rejet d'un avis.
     */
    public function reject(User $user, Review $review): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $review->seller_id === $user->id) {
            if ($review->status === 'rejected') {
                return Response::deny('Cet avis est déjà rejeté.');
            }
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à rejeter cet avis.');
    }

    /**
     * Autorise le marquage comme spam.
     */
    public function markAsSpam(User $user, Review $review): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $review->seller_id === $user->id) {
            if ($review->status === 'spam') {
                return Response::deny('Cet avis est déjà marqué comme spam.');
            }
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à marquer cet avis comme spam.');
    }

    /**
     * Autorise la remise en pending.
     */
    public function reset(User $user, Review $review): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $review->seller_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à réinitialiser cet avis.');
    }

    /**
     * Autorise la recommandation d'un avis.
     */
    public function recommend(User $user, Review $review): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $review->seller_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à recommander cet avis.');
    }

    /**
     * Autorise le vote "utile / pas utile".
     *
     * - Client : ✅ (une seule fois par avis — contrainte DB unique)
     * - Vendeur : ❌ (ne peut pas voter sur les avis de ses produits)
     * - Admin  : ✅
     */
    public function voteHelpful(User $user, Review $review): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller()) {
            return Response::deny('Les vendeurs ne peuvent pas voter sur les avis.');
        }

        // Le client ne peut pas voter sur son propre avis
        if ($review->user_id === $user->id) {
            return Response::deny('Vous ne pouvez pas voter sur votre propre avis.');
        }

        // Le client ne peut voter qu'UNE fois (contrainte DB unique)
        $hasVoted = \App\Models\Review\ReviewVote::where('review_id', $review->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($hasVoted) {
            return Response::deny('Vous avez déjà voté sur cet avis.');
        }

        return Response::allow();
    }
}