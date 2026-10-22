<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Trait : ScopesBySeller
 *
 * Fournit des scopes Eloquent réutilisables pour l'isolation
 * des données par vendeur (multi-tenancy par ligne).
 *
 * Objectif cybersécurité :
 *   Empêcher un vendeur de voir ou manipuler les données
 *   d'un autre vendeur, même s'il connaît les IDs.
 *
 * Pattern d'utilisation :
 *   Product::forSeller($user)->get();
 *   Order::forSeller($user)->where('status', 'pending')->get();
 *   Review::forSeller($user)->count();
 *
 * ⚠️ Règle d'or :
 *   AUCUN contrôleur seller ne doit utiliser `Model::all()`.
 *   TOUJOURS utiliser `Model::forSeller(auth()->user())`.
 *
 * Scopes fournis :
 *   - forSeller($user)  → filtre strictement sur un vendeur
 *   - forOwner($user)   → filtre sur vendeur OU admin (si admin → tout voir)
 *   - forSellerOrAdmin($user) → alias de forOwner (compatibilité)
 *   - forCurrentSeller() → raccourci avec auth()->user()
 */
trait ScopesBySeller
{
    /**
     * Filtre les données appartenant STRICTEMENT à un vendeur.
     *
     * Utilisation typique :
     *   Product::forSeller($user)->get();
     *   → retourne UNIQUEMENT les produits où seller_id = $user->id
     *
     * ⚠️ Si $user n'est pas un vendeur (client), retourne une requête vide
     *    (whereRaw('1=0')) pour éviter toute fuite de données.
     *
     * @param Builder $query
     * @param Model|null $user
     * @return Builder
     */
    public function scopeForSeller(Builder $query, ?Model $user): Builder
    {
        // Sécurité : pas d'utilisateur → requête vide
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Sécurité : pas un vendeur → requête vide
        // (un client ne doit JAMAIS voir les données vendeur)
        if (method_exists($user, 'isSeller') && !$user->isSeller()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            $query->getModel()->getTable() . '.seller_id',
            $user->id
        );
    }

    /**
     * Filtre les données appartenant à un vendeur OU permet à un admin
     * de tout voir.
     *
     * Utilisation typique :
     *   Product::forOwner($user)->get();
     *   → si $user est admin  : retourne TOUS les produits
     *   → si $user est vendeur: retourne UNIQUEMENT ses produits
     *   → si $user est client : retourne une requête vide
     *
     * @param Builder $query
     * @param Model|null $user
     * @return Builder
     */
    public function scopeForOwner(Builder $query, ?Model $user): Builder
    {
        // Sécurité : pas d'utilisateur → requête vide
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Admin : peut tout voir
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $query;
        }

        // Vendeur : voit uniquement ses données
        if (method_exists($user, 'isSeller') && $user->isSeller()) {
            return $query->where(
                $query->getModel()->getTable() . '.seller_id',
                $user->id
            );
        }

        // Client ou autre : requête vide
        return $query->whereRaw('1 = 0');
    }

    /**
     * Alias de `forOwner()` pour compatibilité sémantique.
     *
     * Utile quand on veut exprimer clairement :
     *   "vendeur OU admin peut voir ces données"
     *
     * @param Builder $query
     * @param Model|null $user
     * @return Builder
     */
    public function scopeForSellerOrAdmin(Builder $query, ?Model $user): Builder
    {
        return $this->scopeForOwner($query, $user);
    }

    /**
     * Raccourci pratique : filtre sur le vendeur actuellement connecté.
     *
     * Utilisation typique :
     *   Product::forCurrentSeller()->get();
     *   → utilise automatiquement auth()->user()
     *
     * ⚠️ À n'utiliser QUE dans des contextes authentifiés (middleware auth).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeForCurrentSeller(Builder $query): Builder
    {
        return $this->scopeForSeller($query, Auth::user());
    }

    /**
     * Raccourci pratique : filtre sur le vendeur OU admin actuellement connecté.
     *
     * Utilisation typique :
     *   Product::forCurrentSellerOrAdmin()->get();
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeForCurrentSellerOrAdmin(Builder $query): Builder
    {
        return $this->scopeForOwner($query, Auth::user());
    }

    /**
     * Filtre les modèles SANS vendeur (produits admin / marketplace).
     *
     * Utilisation typique :
     *   Product::withoutSeller()->get();
     *   → retourne uniquement les produits où seller_id IS NULL
     *
     * Utile pour :
     *   - La vitrine client (produits marketplace)
     *   - Les rapports admin (produits non attribués)
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithoutSeller(Builder $query): Builder
    {
        return $query->whereNull(
            $query->getModel()->getTable() . '.seller_id'
        );
    }

    /**
     * Vérifie si un modèle appartient à un vendeur donné.
     *
     * Utilisation typique (dans les Policies) :
     *   if (!$product->belongsToSeller($user)) {
     *       return false;
     *   }
     *
     * Différence avec le scope :
     *   - Le scope filtre une COLLECTION
     *   - Cette méthode vérifie une INSTANCE
     *
     * @param Model|null $user
     * @return bool
     */
    public function belongsToSeller(?Model $user): bool
    {
        if (!$user) {
            return false;
        }

        // Admin : peut tout voir
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        // Vendeur : doit correspondre au seller_id
        return isset($this->seller_id) && $this->seller_id === $user->id;
    }
}