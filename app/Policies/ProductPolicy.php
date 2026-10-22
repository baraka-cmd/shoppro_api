<?php

namespace App\Policies;

use App\Models\Catalog\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy : ProductPolicy
 *
 * Contrôle les autorisations sur les produits.
 *
 * Matrice d'accès :
 *   ┌────────────────────┬──────────┬─────────┬────────┐
 *   │ Action             │ Client   │ Vendeur │ Admin  │
 *   ├────────────────────┼──────────┼─────────┼────────┤
 *   │ viewAny (liste)    │ ✅ public │ ⚠️ ses  │ ✅ tout │
 *   │ view               │ ✅ public │ ⚠️ ses  │ ✅ tout │
 *   │ create             │ ❌        │ ✅ si KYC│ ✅     │
 *   │ update             │ ❌        │ ⚠️ ses  │ ✅ tout │
 *   │ delete             │ ❌        │ ⚠️ ses  │ ✅ tout │
 *   │ restore            │ ❌        │ ❌      │ ✅     │
 *   │ forceDelete        │ ❌        │ ❌      │ ✅     │
 *   │ publish            │ ❌        │ ⚠️ ses  │ ✅     │
 *   │ unpublish          │ ❌        │ ⚠️ ses  │ ✅     │
 *   │ uploadImages       │ ❌        │ ⚠️ ses  │ ✅     │
 *   │ deleteImage        │ ❌        │ ⚠️ ses  │ ✅     │
 *   │ duplicate          │ ❌        │ ⚠️ ses  │ ✅     │
 *   └────────────────────┴──────────┴─────────┴────────┘
 *
 * ⚠️ Règles métier :
 *   - Un vendeur ne peut publier QUE s'il est KYC verified et is_active
 *   - Un produit "admin" (seller_id = NULL) appartient à la marketplace
 *   - Un vendeur ne peut PAS toucher un produit admin
 *
 * Réponses 403 vs 404 :
 *   - `view`  : 404 si le produit n'existe pas (ne pas révéler l'existence)
 *   - `update`: 403 si le produit existe mais n'appartient pas au vendeur
 *   - `delete`: 403 idem
 *
 * @see \App\Models\Concerns\ScopesBySeller
 */
class ProductPolicy
{
    /**
     * Autorise la liste des produits.
     *
     * - Client : OK (route publique)
     * - Vendeur : OK (mais sera filtré par scope forSeller)
     * - Admin  : OK (voit tout)
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Autorise la consultation d'un produit.
     *
     * - Client : OK (route publique)
     * - Vendeur : OK si SES produits OU produits marketplace (seller_id = null)
     * - Admin  : OK
     *
     * Note : la route publique /products/{id} n'utilise PAS cette policy
     *        (elle utilise `viewPublic`). Cette méthode est pour la partie seller.
     */
    public function view(?User $user, Product $product): Response
    {
        // Produit marketplace (seller_id = null) : tout le monde peut voir
        if ($product->seller_id === null) {
            return Response::allow();
        }

        if (!$user) {
            return Response::denyAsNotFound();
        }

        // Admin : peut tout voir
        if ($user->isAdmin()) {
            return Response::allow();
        }

        // Vendeur : uniquement SES produits
        if ($user->isSeller() && $product->seller_id === $user->id) {
            return Response::allow();
        }

        // Sinon : 404 (ne pas révéler l'existence du produit)
        return Response::denyAsNotFound();
    }

    /**
     * Vue publique d'un produit (vitrine client).
     * Aucune restriction : tout le monde peut voir les produits actifs.
     */
    public function viewPublic(?User $user, Product $product): bool
    {
        return $product->is_active;
    }

    /**
     * Autorise la création d'un produit.
     *
     * - Client : ❌
     * - Vendeur : ✅ UNIQUEMENT si KYC verified + is_active
     * - Admin  : ✅
     */
    public function create(User $user): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if (!$user->isSeller()) {
            return Response::deny('Seuls les vendeurs et administrateurs peuvent créer des produits.');
        }

        // Vérifier que le profil vendeur existe et est actif
        $profile = $user->sellerProfile;

        if (!$profile) {
            return Response::deny('Profil vendeur introuvable.');
        }

        if (!$profile->is_active) {
            return Response::deny('Votre compte vendeur est désactivé.');
        }

        if (!$profile->isKycVerified()) {
            return Response::deny('Votre KYC doit être validé avant de créer un produit.');
        }

        return Response::allow();
    }

    /**
     * Autorise la modification d'un produit.
     *
     * - Client : ❌
     * - Vendeur : ⚠️ UNIQUEMENT ses produits
     * - Admin  : ✅ tout
     */
    public function update(User $user, Product $product): Response
    {
        // Admin : peut tout modifier
        if ($user->isAdmin()) {
            return Response::allow();
        }

        // Vendeur : uniquement ses produits
        if ($user->isSeller()) {
            if ($product->seller_id === $user->id) {
                return Response::allow();
            }

            // Vendeur essaie de modifier un produit d'un autre vendeur ou admin
            return Response::deny('Vous n\'êtes pas autorisé à modifier ce produit.');
        }

        // Client : ❌
        return Response::deny('Action réservée aux vendeurs et administrateurs.');
    }

    /**
     * Autorise la suppression d'un produit.
     *
     * - Client : ❌
     * - Vendeur : ⚠️ ses produits (soft delete uniquement)
     * - Admin  : ✅ (soft delete)
     *
     * Note : le hard delete (forceDelete) est interdit aux vendeurs.
     */
    public function delete(User $user, Product $product): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $product->seller_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à supprimer ce produit.');
    }

    /**
     * Autorise la restauration d'un produit supprimé.
     * Réservé à l'admin (un vendeur ne peut pas restaurer un produit).
     */
    public function restore(User $user, Product $product): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Seuls les administrateurs peuvent restaurer un produit.');
    }

    /**
     * Autorise la suppression définitive.
     * Réservé à l'admin.
     */
    public function forceDelete(User $user, Product $product): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Seuls les administrateurs peuvent supprimer définitivement un produit.');
    }

    /**
     * Autorise la publication d'un produit (brouillon → publié).
     *
     * Conditions strictes :
     *   - Vendeur propriétaire du produit
     *   - KYC validé
     *   - Profil vendeur actif
     *   - Produit non déjà publié
     *   - Prix > 0
     *   - Catégorie définie
     */
    public function publish(User $user, Product $product): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if (!$user->isSeller() || $product->seller_id !== $user->id) {
            return Response::deny('Vous n\'êtes pas autorisé à publier ce produit.');
        }

        $profile = $user->sellerProfile;

        if (!$profile || !$profile->canPublishProducts()) {
            return Response::deny('Votre compte vendeur doit être validé pour publier un produit.');
        }

        if ($product->is_active) {
            return Response::deny('Ce produit est déjà publié.');
        }

        if ($product->price <= 0) {
            return Response::deny('Le prix du produit doit être supérieur à 0.');
        }

        if (!$product->category_id) {
            return Response::deny('La catégorie du produit est obligatoire.');
        }

        return Response::allow();
    }

    /**
     * Autorise la dépublication d'un produit (publié → brouillon).
     */
    public function unpublish(User $user, Product $product): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $product->seller_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à dépublier ce produit.');
    }

    /**
     * Autorise l'upload d'images sur un produit.
     */
    public function uploadImages(User $user, Product $product): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $product->seller_id === $user->id) {
            $profile = $user->sellerProfile;
            if (!$profile || !$profile->is_active) {
                return Response::deny('Votre compte vendeur est désactivé.');
            }
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à modifier les images de ce produit.');
    }

    /**
     * Autorise la suppression d'une image d'un produit.
     */
    public function deleteImage(User $user, Product $product): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $product->seller_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à supprimer les images de ce produit.');
    }

    /**
     * Autorise la duplication d'un produit.
     * Le duplicata appartiendra automatiquement au même vendeur.
     */
    public function duplicate(User $user, Product $product): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isSeller() && $product->seller_id === $user->id) {
            $profile = $user->sellerProfile;
            if (!$profile || !$profile->canPublishProducts()) {
                return Response::deny('Votre compte vendeur doit être validé pour dupliquer un produit.');
            }
            return Response::allow();
        }

        return Response::deny('Vous n\'êtes pas autorisé à dupliquer ce produit.');
    }
}