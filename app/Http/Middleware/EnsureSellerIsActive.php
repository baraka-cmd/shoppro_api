<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware : EnsureSellerIsActive
 *
 * Vérifie que le vendeur connecté est :
 *   1. Authentifié
 *   2. A le rôle "seller" (ou "admin")
 *   3. A un profil vendeur (`seller_profiles`)
 *   4. Le profil est actif (`is_active = true`)
 *   5. Le KYC est validé (`kyc_status = 'verified'`)
 *
 * Objectifs cybersécurité :
 *   - Bloquer un vendeur dont le KYC est pending/rejected
 *   - Bloquer un vendeur désactivé (is_active = false)
 *   - Bloquer un vendeur dont le compte a été soft-deleted
 *
 * Cas d'usage :
 *   Route::middleware(['auth:sanctum', 'role:seller,admin', 'seller.active'])->group(...)
 *
 * Comportement :
 *   - Admin      : passe TOUJOURS (n'a pas besoin de profil vendeur)
 *   - Vendeur OK : passe
 *   - Vendeur KO : 403 avec message adapté
 *   - Non authentifié : 401 (géré en amont par auth:sanctum)
 *
 * ⚠️ Ordre d'application dans la chaîne :
 *   1. auth:sanctum
 *   2. role:seller,admin   (CheckRole)
 *   3. seller.active       (ce middleware) ← APRÈS les 2 premiers
 *
 * @package App\Http\Middleware
 */
class EnsureSellerIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // ============================================
        // 1. PAS D'UTILISATEUR → 401
        // ============================================
        // En théorie déjà géré par auth:sanctum, mais fail-safe.
        if (!$user) {
            return $this->unauthorized(
                'Non authentifié. Veuillez vous connecter.'
            );
        }

        // ============================================
        // 2. ADMIN → TOUJOURS AUTORISÉ
        // ============================================
        // L'admin peut accéder à tous les endpoints, y compris /seller/*
        // (utile pour support client / debug).
        if ($user->isAdmin()) {
            return $next($request);
        }

        // ============================================
        // 3. VÉRIFICATION DU RÔLE SELLER
        // ============================================
        if (!$user->isSeller()) {
            return $this->forbidden(
                'Accès réservé aux vendeurs.'
            );
        }

        // ============================================
        // 4. VÉRIFICATION DU PROFIL VENDEUR
        // ============================================
        $profile = $user->sellerProfile;

        if (!$profile) {
            return $this->forbidden(
                'Profil vendeur introuvable. Contactez le support.'
            );
        }

        // ============================================
        // 5. VÉRIFICATION KYC
        // ============================================
        if (!$profile->isKycVerified()) {
            $message = match ($profile->kyc_status) {
                'pending'  => 'Votre KYC est en cours de vérification. Vous recevrez une notification dès validation.',
                'rejected' => 'Votre KYC a été refusé. ' . ($profile->kyc_rejection_reason ?? 'Contactez le support.'),
                default    => 'Votre KYC doit être validé pour accéder à cette section.',
            };

            return $this->forbidden($message, [
                'kyc_status'           => $profile->kyc_status,
                'kyc_rejection_reason' => $profile->kyc_rejection_reason,
            ]);
        }

        // ============================================
        // 6. VÉRIFICATION COMPTE ACTIF
        // ============================================
        if (!$profile->is_active) {
            return $this->forbidden(
                'Votre compte vendeur est désactivé. Contactez le support.'
            );
        }

        // ============================================
        // 7. VÉRIFICATION SOFT DELETE
        // ============================================
        if ($profile->trashed()) {
            return $this->forbidden(
                'Votre compte vendeur a été supprimé.'
            );
        }

        // ============================================
        // 8. TOUT EST OK → PASSER
        // ============================================
        // On peut enrichir la requête avec le profil pour éviter
        // une requête DB supplémentaire dans les contrôleurs.
        $request->attributes->set('seller_profile', $profile);

        return $next($request);
    }

    // ============================================
    // HELPERS DE RÉPONSE
    // ============================================

    /**
     * Réponse 401 (non authentifié).
     */
    private function unauthorized(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code'    => 'UNAUTHENTICATED',
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Réponse 403 (accès refusé).
     *
     * @param string $message
     * @param array $extra
     * @return Response
     */
    private function forbidden(string $message, array $extra = []): Response
    {
        $payload = [
            'success' => false,
            'message' => $message,
            'code'    => 'SELLER_NOT_ACTIVE',
        ];

        if (!empty($extra)) {
            $payload['data'] = $extra;
        }

        return response()->json($payload, Response::HTTP_FORBIDDEN);
    }
}