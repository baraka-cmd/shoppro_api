<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Seller\SellerProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Observer : UserObserver
 *
 * Responsabilités :
 *   1. Auto-créer un SellerProfile dès qu'un user reçoit le rôle 'seller'
 *   2. Auto-créer un profil vendeur lors de la création directe
 *      d'un user ayant déjà le rôle seller
 *   3. Nettoyer les données vendeur lors d'une suppression soft
 *
 * ⚠️ Cas d'usage surveillés :
 *   - AdminController::changeRole($user, 'seller') → attach role → observer créé
 *   - AdminController::store() avec role='seller'   → created → observer créé
 *   - User::create([...]) + assignRole('seller')    → created (pas de role) + observer manuel requis
 *
 * ⚠️ Piège : la relation `roles()` est BelongsToMany.
 *   - `created` ne voit PAS encore les rôles attachés
 *   - `attach('seller')` déclenche un événement pivot, PAS `updated` sur User
 *   - Solution : écouter les événements `role_user` via EventServiceProvider
 *     OU créer manuellement le profil dans AdminController après assignRole()
 *
 * → On expose une méthode STATIQUE `ensureSellerProfile(User $user)`
 *   que les contrôleurs admin peuvent appeler EXPLICITEMENT.
 *
 * Défense en profondeur :
 *   - Si la création du profil échoue, l'erreur est loggée mais n'interrompt
 *     PAS l'assignation du rôle (best-effort)
 *   - On utilise `firstOrCreate` pour éviter les doublons
 *
 * @package App\Observers
 */
class UserObserver
{
    /**
     * Après la création d'un User.
     *
     * Si l'utilisateur a déjà le rôle 'seller' (cas rare : création
     * avec role en même temps), on crée son SellerProfile.
     */
    public function created(User $user): void
    {
        // Sécurité : ne pas crasher si les rôles ne sont pas encore attachés
        try {
            if ($user->isSeller()) {
                self::ensureSellerProfile($user);
            }
        } catch (\Throwable $e) {
            Log::warning('UserObserver::created - Impossible de créer le profil vendeur', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Après la mise à jour d'un User.
     *
     * Cas : un admin passe un user en role 'seller' via un PUT.
     * On vérifie à chaque update si le profil vendeur doit exister.
     */
    public function updated(User $user): void
    {
        try {
            if ($user->isSeller()) {
                self::ensureSellerProfile($user);
            }
        } catch (\Throwable $e) {
            Log::warning('UserObserver::updated - Impossible de créer le profil vendeur', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Avant la suppression soft d'un User.
     *
     * ⚠️ On ne supprime PAS le SellerProfile :
     *   - Traçabilité financière obligatoire
     *   - Les transactions/payouts y sont liés
     *   - On désactive simplement le profil
     */
    public function deleting(User $user): void
    {
        try {
            $profile = $user->sellerProfile;

            if ($profile && !$profile->trashed()) {
                $profile->update([
                    'is_active' => false,
                ]);

                Log::info('UserObserver::deleting - Profil vendeur désactivé', [
                    'user_id'  => $user->id,
                    'profile'  => $profile->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('UserObserver::deleting - Impossible de désactiver le profil vendeur', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // ============================================
    // MÉTHODE STATIQUE UTILITAIRE
    // ============================================

    /**
     * Garantit qu'un SellerProfile existe pour un user donné.
     *
     * À appeler :
     *   - Depuis AdminController::changeRole('seller')
     *   - Depuis AdminController::store() quand role='seller'
     *   - Depuis User::assignRole('seller') si on veut être exhaustif
     *
     * Idempotent :
     *   - Si le profil existe déjà → ne fait rien
     *   - Si le user n'est pas vendeur → ne fait rien
     *
     * Sécurisé :
     *   - Utilise une transaction DB
     *   - Utilise firstOrCreate pour éviter les doublons
     *   - Génère un slug unique
     *
     * @param User $user
     * @return SellerProfile|null
     */
    public static function ensureSellerProfile(User $user): ?SellerProfile
    {
        // 1. Vérifier que le user est bien vendeur
        if (!$user->isSeller()) {
            return null;
        }

        // 2. Vérifier qu'un profil n'existe pas déjà (sans créer)
        $existing = SellerProfile::where('user_id', $user->id)->first();

        if ($existing) {
            return $existing;
        }

        // 3. Créer le profil dans une transaction
        try {
            return DB::transaction(function () use ($user) {
                // Générer un slug unique
                $baseSlug = Str::slug($user->name ?: 'boutique-' . $user->id);
                $slug     = $baseSlug;
                $suffix   = 1;

                while (SellerProfile::withTrashed()->where('shop_slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $suffix;
                    $suffix++;
                }

                // Créer le profil
                $profile = SellerProfile::create([
                    'user_id'          => $user->id,
                    'shop_name'        => $user->name . ' - Boutique',
                    'shop_slug'        => $slug,
                    'email'            => $user->email,
                    'phone'            => $user->profile?->phone,
                    'country'          => $user->profile?->country ?? 'Cameroun',
                    'city'             => $user->profile?->city,
                    'kyc_status'       => 'pending',
                    'commission_rate'  => 10.00,
                    'balance'          => 0,
                    'pending_balance'  => 0,
                    'is_active'        => true,
                ]);

                Log::info('SellerProfile créé automatiquement', [
                    'user_id'    => $user->id,
                    'profile_id' => $profile->id,
                    'shop_slug'  => $slug,
                ]);

                return $profile;
            });
        } catch (\Throwable $e) {
            Log::error('Impossible de créer le SellerProfile', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return null;
        }
    }
}