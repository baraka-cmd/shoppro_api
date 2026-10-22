<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Seller\UpdateSellerProfileRequest;
use App\Http\Requests\Api\Seller\SubmitKycRequest;
use App\Http\Resources\Api\Seller\SellerProfileResource;
use App\Models\Seller\SellerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Controller : SellerProfileController
 *
 * Gère le profil boutique du vendeur :
 *   - show()          : GET /seller/profile
 *   - update()        : PUT /seller/profile
 *   - uploadLogo()    : POST /seller/profile/logo
 *   - uploadBanner()  : POST /seller/profile/banner
 *   - submitKyc()     : POST /seller/profile/submit-kyc
 *
 * Sécurité :
 *   - auth:sanctum (middleware global)
 *   - role:seller,admin (middleware global)
 *   - Le vendeur ne peut accéder QU'À son propre profil
 *   - L'admin peut accéder à n'importe quel profil (via ?user_id=X)
 *
 * ⚠️ Pas de middleware seller.active sur ce contrôleur :
 *   Sinon un vendeur KYC pending ne pourrait pas soumettre son KYC.
 *
 * @package App\Http\Controllers\Api\Seller
 */
class SellerProfileController extends Controller
{
    /**
     * GET /api/v1/seller/profile
     *
     * Retourne le profil boutique du vendeur connecté.
     * Admin : peut passer ?user_id=X pour voir un profil spécifique.
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $profile = $this->resolveProfile($request);

            return $this->success(
                'Profil boutique récupéré avec succès.',
                new SellerProfileResource($profile->load('user'))
            );
        } catch (Throwable $e) {
            Log::error('SellerProfileController::show', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Impossible de récupérer le profil : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * PUT /api/v1/seller/profile
     *
     * Met à jour les champs modifiables du profil boutique.
     * Si le nom change, un nouveau slug est généré (avec vérification d'unicité).
     */
    public function update(UpdateSellerProfileRequest $request): JsonResponse
    {
        try {
            $profile = $this->resolveProfile($request);
            $data    = $request->validated();

            DB::beginTransaction();

            // Génération du slug si le nom change
            if (isset($data['shop_name']) && $data['shop_name'] !== $profile->shop_name) {
                $data['shop_slug'] = $this->generateUniqueSlug(
                    $data['shop_name'],
                    $profile->id
                );
            }

            $profile->update($data);

            DB::commit();

            Log::info('SellerProfile mis à jour', [
                'profile_id' => $profile->id,
                'user_id'    => $profile->user_id,
                'fields'     => array_keys($data),
            ]);

            return $this->success(
                'Profil boutique mis à jour avec succès.',
                new SellerProfileResource($profile->fresh()->load('user'))
            );
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('SellerProfileController::update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Impossible de mettre à jour le profil : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * POST /api/v1/seller/profile/logo
     *
     * Upload du logo de la boutique.
     * Remplace l'ancien logo (suppression du fichier précédent).
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => [
                'required',
                \Illuminate\Validation\Rules\File::image()
                    ->types(['jpeg', 'jpg', 'png', 'webp'])
                    ->max(2 * 1024), // 2 Mo
            ],
        ], [
            'logo.required' => 'Le logo est obligatoire.',
            'logo.image'    => 'Le logo doit être une image (JPG, PNG, WEBP).',
            'logo.max'      => 'Le logo ne peut pas dépasser 2 Mo.',
        ]);

        try {
            $profile = $this->resolveProfile($request);

            DB::beginTransaction();

            // Supprimer l'ancien logo si présent
            if ($profile->logo && Storage::disk('public')->exists($profile->logo)) {
                Storage::disk('public')->delete($profile->logo);
            }

            // Stocker le nouveau logo
            $path = $request->file('logo')->store(
                "sellers/{$profile->user_id}",
                'public'
            );

            $profile->update(['logo' => $path]);

            DB::commit();

            Log::info('Logo boutique uploadé', [
                'profile_id' => $profile->id,
                'path'       => $path,
            ]);

            return $this->success(
                'Logo mis à jour avec succès.',
                new SellerProfileResource($profile->fresh())
            );
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('SellerProfileController::uploadLogo', [
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                'Impossible d\'uploader le logo : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * POST /api/v1/seller/profile/banner
     *
     * Upload de la bannière de la boutique.
     */
    public function uploadBanner(Request $request): JsonResponse
    {
        $request->validate([
            'banner' => [
                'required',
                \Illuminate\Validation\Rules\File::image()
                    ->types(['jpeg', 'jpg', 'png', 'webp'])
                    ->max(5 * 1024), // 5 Mo (bannière plus grande)
            ],
        ], [
            'banner.required' => 'La bannière est obligatoire.',
            'banner.image'    => 'La bannière doit être une image (JPG, PNG, WEBP).',
            'banner.max'      => 'La bannière ne peut pas dépasser 5 Mo.',
        ]);

        try {
            $profile = $this->resolveProfile($request);

            DB::beginTransaction();

            if ($profile->banner && Storage::disk('public')->exists($profile->banner)) {
                Storage::disk('public')->delete($profile->banner);
            }

            $path = $request->file('banner')->store(
                "sellers/{$profile->user_id}",
                'public'
            );

            $profile->update(['banner' => $path]);

            DB::commit();

            Log::info('Bannière boutique uploadée', [
                'profile_id' => $profile->id,
                'path'       => $path,
            ]);

            return $this->success(
                'Bannière mise à jour avec succès.',
                new SellerProfileResource($profile->fresh())
            );
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('SellerProfileController::uploadBanner', [
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                'Impossible d\'uploader la bannière : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * POST /api/v1/seller/profile/submit-kyc
     *
     * Soumet le KYC pour vérification.
     * Statut : pending → (submitted)
     * L'admin validera ensuite pour passer à verified.
     */
    public function submitKyc(SubmitKycRequest $request): JsonResponse
    {
        try {
            $profile = $this->resolveProfile($request);

            // Vérification supplémentaire : éviter le spam de soumissions
            if ($profile->kyc_status === 'verified') {
                return $this->error(
                    'Votre KYC est déjà validé.',
                    Response::HTTP_CONFLICT
                );
            }

            // Si le KYC est déjà pending avec un document soumis,
            // on empêche la re-soumission tant que l'admin n'a pas statué
            // (on vérifie via un flag ou la présence d'un fichier KYC)
            // → À adapter selon si tu ajoutes une colonne `kyc_submitted_at`

            DB::beginTransaction();

            $data = $request->validated();

            // Stocker les fichiers
            $idDocumentPath = $request->file('id_document')->store(
                "sellers/{$profile->user_id}/kyc",
                'public'
            );

            $businessLicensePath = null;
            if ($request->hasFile('business_license')) {
                $businessLicensePath = $request->file('business_license')->store(
                    "sellers/{$profile->user_id}/kyc",
                    'public'
                );
            }

            $selfiePath = null;
            if ($request->hasFile('selfie')) {
                $selfiePath = $request->file('selfie')->store(
                    "sellers/{$profile->user_id}/kyc",
                    'public'
                );
            }

            // Mettre à jour le profil avec le statut pending
            // (les fichiers sont stockés mais le statut reste pending
            //  jusqu'à validation admin)
            $profile->update([
                'kyc_status'           => 'pending',
                'kyc_rejection_reason' => null,
                'kyc_verified_at'      => null,
            ]);

            DB::commit();

            Log::info('KYC soumis pour vérification', [
                'profile_id'  => $profile->id,
                'user_id'     => $profile->user_id,
                'id_type'     => $data['id_type'] ?? null,
                'has_license' => !is_null($businessLicensePath),
                'has_selfie'  => !is_null($selfiePath),
            ]);

            return $this->success(
                'Votre KYC a été soumis. Notre équipe le vérifiera sous 24-48h.',
                new SellerProfileResource($profile->fresh()),
                Response::HTTP_OK
            );
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('SellerProfileController::submitKyc', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Impossible de soumettre le KYC : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // HELPERS PRIVÉS
    // ============================================

    /**
     * Résout le profil vendeur cible selon le contexte.
     *
     * - Vendeur : son propre profil
     * - Admin : profil du vendeur via ?user_id=X
     *
     * @param Request $request
     * @return SellerProfile
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function resolveProfile(Request $request): SellerProfile
    {
        $user = $request->user();

        // Admin : peut cibler un autre vendeur
        if ($user->isAdmin() && $request->has('user_id')) {
            $profile = SellerProfile::where('user_id', $request->input('user_id'))->first();

            if (!$profile) {
                abort(
                    Response::HTTP_NOT_FOUND,
                    'Aucun profil vendeur trouvé pour cet utilisateur.'
                );
            }

            return $profile;
        }

        // Vendeur : son propre profil
        $profile = $user->sellerProfile;

        if (!$profile) {
            abort(
                Response::HTTP_NOT_FOUND,
                'Profil vendeur introuvable. Contactez le support.'
            );
        }

        return $profile;
    }

    /**
     * Génère un slug unique pour la boutique.
     *
     * @param string $name
     * @param int|null $ignoreId
     * @return string
     */
    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug     = $baseSlug;
        $suffix   = 1;

        $query = SellerProfile::withTrashed()->where('shop_slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        while ($query->exists()) {
            $slug = $baseSlug . '-' . $suffix;

            $query = SellerProfile::withTrashed()->where('shop_slug', $slug);

            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }

            $suffix++;
        }

        return $slug;
    }

    // ============================================
    // HELPERS DE RÉPONSE STANDARDISÉE
    // ============================================

    /**
     * Réponse succès standardisée.
     */
    private function success(string $message, $data = null, int $status = Response::HTTP_OK): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    /**
     * Réponse erreur standardisée.
     */
    private function error(string $message, int $status = Response::HTTP_BAD_REQUEST, $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    public function dismissOnboarding(Request $request): JsonResponse
{
    $user = $request->user();
    $profile = $user->sellerProfile;

    if (!$profile) {
        return $this->error('Profil introuvable.', 404);
    }

    $profile->dismissOnboarding();

    return response()->json([
        'success' => true,
        'message' => 'Checklist masquée.',
    ]);
}
}