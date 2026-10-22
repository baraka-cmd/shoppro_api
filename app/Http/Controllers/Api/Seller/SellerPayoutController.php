<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Seller\SellerPayoutResource;
use App\Models\Seller\SellerPayout;
use App\Models\Seller\SellerProfile;
use App\Models\Seller\SellerTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Controller : SellerPayoutController
 *
 * Gère les versements (payouts) du vendeur.
 *
 * Workflow :
 *   pending → processing → completed
 *                        ↘ failed
 *                        ↘ cancelled
 *
 * ⚠️ RÈGLES CRITIQUES DE SÉCURITÉ FINANCIÈRE :
 *   1. Un vendeur ne peut demander un versement QUE si :
 *      - Son KYC est validé (verified)
 *      - Son compte est actif (is_active = true)
 *      - Son balance >= montant demandé
 *      - Aucun payout en cours (pending/processing)
 *   2. Chaque opération financière est TRACÉE (SellerTransaction)
 *   3. Le solde est débité UNIQUEMENT quand le payout passe en 'completed'
 *   4. Le débit du solde est atomique (DB::transaction + lockForUpdate)
 *
 * Actions :
 *   - balance() : Solde disponible + en attente + stats
 *   - index()   : Liste paginée des payouts
 *   - show()    : Détail d'un payout
 *   - request() : Créer une demande de versement
 *   - cancel()  : Annuler un payout pending
 *
 * @package App\Http\Controllers\Api\Seller
 */
class SellerPayoutController extends Controller
{
    /**
     * Méthodes de payout autorisées.
     */
    private const ALLOWED_METHODS = [
        'mobile_money',
        'bank_transfer',
        'paypal',
    ];

    /**
     * Montant minimum de payout (1000 FCFA).
     */
    private const MIN_PAYOUT_AMOUNT = 1000;

    /**
     * Frais de payout selon la méthode.
     */
    private const PAYOUT_FEES = [
        'mobile_money'  => 0.02,   // 2%
        'bank_transfer' => 0.01,   // 1%
        'paypal'        => 0.03,   // 3%
    ];

    // ============================================
    // BALANCE — SOLDE DU VENDEUR
    // ============================================

    /**
     * GET /api/v1/seller/payouts/balance
     *
     * Retourne le solde du vendeur avec les statistiques.
     */
    public function balance(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $profile = $user->sellerProfile;

            if (!$profile) {
                return $this->error(
                    'Profil vendeur introuvable.',
                    Response::HTTP_NOT_FOUND
                );
            }

            // ============================================
            // SOLDE
            // ============================================
            $available = (float) $profile->balance;
            $pending = (float) $profile->pending_balance;
            $total = $available + $pending;

            // ============================================
            // PAYOUT EN COURS
            // ============================================
            $activePayout = SellerPayout::where('seller_id', $user->id)
                ->whereIn('status', ['pending', 'processing'])
                ->first();

            // ============================================
            // STATS HISTORIQUES
            // ============================================
            $stats = $this->computePayoutStats($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Solde récupéré avec succès.',
                'data' => [
                    // ============================================
                    // SOLDES
                    // ============================================
                    'available'               => $available,
                    'available_formatted'     => $this->formatPrice($available),
                    'pending'                 => $pending,
                    'pending_formatted'       => $this->formatPrice($pending),
                    'total'                   => $total,
                    'total_formatted'         => $this->formatPrice($total),

                    // ============================================
                    // DISPONIBILITÉ
                    // ============================================
                    'can_request_payout'      => $profile->canRequestPayout()
                        && $available >= self::MIN_PAYOUT_AMOUNT,
                    'min_payout_amount'       => self::MIN_PAYOUT_AMOUNT,
                    'min_payout_amount_formatted' => $this->formatPrice(self::MIN_PAYOUT_AMOUNT),

                    // ============================================
                    // PAYOUT EN COURS (si existe)
                    // ============================================
                    'active_payout' => $activePayout ? [
                        'id'                 => $activePayout->id,
                        'reference'          => $activePayout->reference,
                        'amount'             => (float) $activePayout->amount,
                        'amount_formatted'   => $this->formatPrice($activePayout->amount),
                        'status'             => $activePayout->status,
                        'requested_at'       => $activePayout->requested_at?->toIso8601String(),
                        'requested_at_human' => $activePayout->requested_at?->diffForHumans(),
                    ] : null,

                    // ============================================
                    // CONFIGURATION
                    // ============================================
                    'payout_method'           => $profile->payout_method,
                    'payout_account_masked'   => $this->maskAccount($profile->payout_account),
                    'has_payout_method'       => !empty($profile->payout_method)
                        && !empty($profile->payout_account),
                    'commission_rate'         => (float) $profile->commission_rate,

                    // ============================================
                    // STATISTIQUES
                    // ============================================
                    'stats' => $stats,
                ],
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerPayoutController::balance', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de récupérer le solde : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // INDEX — LISTE DES PAYOUTS
    // ============================================

    /**
     * GET /api/v1/seller/payouts
     *
     * Liste paginée des payouts du vendeur.
     *
     * Query params :
     *   - status   : all | pending | processing | completed | failed | cancelled
     *   - per_page : 20 par défaut, max 50
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $query = SellerPayout::query()
                ->where('seller_id', $user->id);

            // ============================================
            // FILTRE : Statut
            // ============================================
            $status = $request->input('status', 'all');
            if ($status !== 'all' && in_array(
                $status,
                ['pending', 'processing', 'completed', 'failed', 'cancelled'],
                true
            )) {
                $query->where('status', $status);
            }

            // ============================================
            // PAGINATION
            // ============================================
            $perPage = min((int) $request->input('per_page', 20), 50);
            $payouts = $query->latest()->paginate($perPage);

            // ============================================
            // STATISTIQUES
            // ============================================
            $stats = $this->computePayoutStats($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Payouts récupérés avec succès.',
                'data'    => SellerPayoutResource::collection($payouts),
                'pagination' => [
                    'total'        => $payouts->total(),
                    'count'        => $payouts->count(),
                    'per_page'     => $payouts->perPage(),
                    'current_page' => $payouts->currentPage(),
                    'last_page'    => $payouts->lastPage(),
                    'has_more'     => $payouts->hasMorePages(),
                ],
                'stats' => $stats,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerPayoutController::index', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de récupérer les payouts : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // SHOW — DÉTAIL D'UN PAYOUT
    // ============================================

    /**
     * GET /api/v1/seller/payouts/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            $payout = SellerPayout::where('seller_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$payout) {
                return $this->error(
                    'Payout introuvable.',
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Payout récupéré avec succès.',
                'data'    => new SellerPayoutResource($payout),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerPayoutController::show', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de récupérer le payout : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // REQUEST — DEMANDER UN VERSEMENT
    // ============================================

    /**
     * POST /api/v1/seller/payouts/request
     *
     * Crée une demande de versement.
     *
     * Body :
     *   - amount         : montant demandé (required, min:1000)
     *   - method         : mobile_money | bank_transfer | paypal
     *   - account        : compte destinataire (requis si pas dans le profil)
     *   - note           : note optionnelle
     */
    public function request(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Validation
            $validated = $request->validate([
                'amount'  => 'required|numeric|min:' . self::MIN_PAYOUT_AMOUNT,
                'method'  => 'required|string|in:' . implode(',', self::ALLOWED_METHODS),
                'account' => 'nullable|string|max:150',
                'note'    => 'nullable|string|max:500',
            ], [
                'amount.required' => 'Le montant est obligatoire.',
                'amount.numeric'  => 'Le montant doit être un nombre.',
                'amount.min'      => 'Le montant minimum est de ' . self::MIN_PAYOUT_AMOUNT . ' FCFA.',
                'method.required' => 'La méthode de versement est obligatoire.',
                'method.in'       => 'La méthode sélectionnée n\'est pas valide.',
                'account.max'     => 'Le compte ne peut pas dépasser 150 caractères.',
                'note.max'        => 'La note ne peut pas dépasser 500 caractères.',
            ]);

            $amount = (float) $validated['amount'];
            $method = $validated['method'];

            // ============================================
            // VÉRIFICATION DU PROFIL
            // ============================================
            $profile = $user->sellerProfile;

            if (!$profile) {
                return $this->error(
                    'Profil vendeur introuvable.',
                    Response::HTTP_NOT_FOUND
                );
            }

            if (!$profile->isKycVerified()) {
                return $this->error(
                    'Votre KYC doit être validé pour demander un versement.',
                    Response::HTTP_FORBIDDEN
                );
            }

            if (!$profile->is_active) {
                return $this->error(
                    'Votre compte vendeur est désactivé.',
                    Response::HTTP_FORBIDDEN
                );
            }

            // ============================================
            // VÉRIFICATION DU SOLDE
            // ============================================
            if ($amount > $profile->balance) {
                return $this->error(
                    'Solde insuffisant. Votre solde disponible est de '
                    . $this->formatPrice($profile->balance) . '.',
                    Response::HTTP_CONFLICT
                );
            }

            // ============================================
            // VÉRIFICATION PAYOUT EN COURS
            // ============================================
            $activePayout = SellerPayout::where('seller_id', $user->id)
                ->whereIn('status', ['pending', 'processing'])
                ->exists();

            if ($activePayout) {
                return $this->error(
                    'Vous avez déjà un versement en cours de traitement. Veuillez attendre sa complétion.',
                    Response::HTTP_CONFLICT
                );
            }

            // ============================================
            // COMPTE DESTINATAIRE
            // ============================================
            $account = $validated['account'] ?? $profile->payout_account;

            if (empty($account)) {
                return $this->error(
                    'Veuillez renseigner un compte de versement.',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            // ============================================
            // CALCUL DES FRAIS
            // ============================================
            $feeRate = self::PAYOUT_FEES[$method] ?? 0;
            $fee = round($amount * $feeRate, 2);
            $netAmount = $amount - $fee;

            if ($netAmount <= 0) {
                return $this->error(
                    'Le montant net après frais doit être positif.',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            // ============================================
            // CRÉATION DU PAYOUT (TRANSACTION)
            // ============================================
            DB::beginTransaction();

            // Lock pessimiste sur le profil pour éviter les doubles demandes
            $lockedProfile = SellerProfile::lockForUpdate()->find($profile->id);

            // Re-vérifier le solde après lock
            if ($amount > $lockedProfile->balance) {
                DB::rollBack();
                return $this->error(
                    'Solde insuffisant (vérification après lock).',
                    Response::HTTP_CONFLICT
                );
            }

            // Créer le payout
            $payout = SellerPayout::create([
                'reference'         => $this->generateReference(),
                'seller_id'         => $user->id,
                'amount'            => $amount,
                'fee'               => $fee,
                'net_amount'        => $netAmount,
                'currency'          => 'FCFA',
                'method'            => $method,
                'account'           => $account,
                'account_name'      => $user->name,
                'status'            => 'pending',
                'requested_at'      => now(),
                'note'              => $validated['note'] ?? null,
            ]);

            // Créer une transaction (débit en attente, pas encore effectif)
            // ⚠️ Le débit réel se fera quand le payout passera en 'completed'
            SellerTransaction::create([
                'reference'         => 'TXN-' . $payout->reference,
                'seller_id'         => $user->id,
                'type'              => 'payout',
                'direction'         => 'debit',
                'amount'            => $amount,
                'commission_amount' => 0,
                'net_amount'        => $amount,
                'currency'          => 'FCFA',
                'description'       => 'Demande de payout #' . $payout->reference,
                'payout_id'         => $payout->id,
            ]);

            DB::commit();

            Log::info('SellerPayout requested', [
                'payout_id' => $payout->id,
                'seller_id' => $user->id,
                'amount'    => $amount,
                'method'    => $method,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Votre demande de versement a été enregistrée. '
                    . 'Elle sera traitée sous 3-5 jours ouvrés.',
                'data'    => new SellerPayoutResource($payout),
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerPayoutController::request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error(
                'Impossible de créer la demande : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // CANCEL — ANNULER UN PAYOUT PENDING
    // ============================================

    /**
     * POST /api/v1/seller/payouts/{id}/cancel
     *
     * Annule un payout en statut 'pending' uniquement.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            DB::beginTransaction();

            $payout = SellerPayout::where('seller_id', $user->id)
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$payout) {
                DB::rollBack();
                return $this->error(
                    'Payout introuvable.',
                    Response::HTTP_NOT_FOUND
                );
            }

            if ($payout->status !== 'pending') {
                DB::rollBack();
                return $this->error(
                    'Seuls les versements en attente peuvent être annulés.',
                    Response::HTTP_CONFLICT
                );
            }

            // Annuler le payout
            $payout->update([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
            ]);

            // Supprimer la transaction de payout associée
            SellerTransaction::where('payout_id', $payout->id)->delete();

            DB::commit();

            Log::info('SellerPayout cancelled', [
                'payout_id' => $payout->id,
                'seller_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demande de versement annulée.',
                'data'    => new SellerPayoutResource($payout->fresh()),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerPayoutController::cancel', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible d\'annuler le payout : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // HELPERS PRIVÉS
    // ============================================

    /**
     * Calcule les statistiques des payouts.
     */
    private function computePayoutStats(int $sellerId): array
    {
        $baseQuery = SellerPayout::where('seller_id', $sellerId);

        $total = (clone $baseQuery)->count();
        $completed = (clone $baseQuery)->where('status', 'completed')->count();
        $pending = (clone $baseQuery)->where('status', 'pending')->count();
        $processing = (clone $baseQuery)->where('status', 'processing')->count();

        $totalCompleted = (clone $baseQuery)
            ->where('status', 'completed')
            ->sum('net_amount');

        $totalCancelled = (clone $baseQuery)
            ->where('status', 'cancelled')
            ->sum('amount');

        return [
            'total_payouts'                => $total,
            'completed_count'              => $completed,
            'pending_count'                => $pending,
            'processing_count'             => $processing,
            'total_withdrawn'              => (float) $totalCompleted,
            'total_withdrawn_formatted'    => $this->formatPrice($totalCompleted),
            'total_cancelled'              => (float) $totalCancelled,
            'total_cancelled_formatted'    => $this->formatPrice($totalCancelled),
        ];
    }

    /**
     * Génère une référence unique de payout.
     * Format : PAYOUT-YYYYMMDD-XXXXXX
     */
    private function generateReference(): string
    {
        $prefix = 'PAYOUT';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));

        return $prefix . '-' . $date . '-' . $random;
    }

    /**
     * Masque un compte (IBAN, mobile money, email).
     */
    private function maskAccount(?string $account): ?string
    {
        if (empty($account)) return null;

        if (str_contains($account, '@')) {
            [$local, $domain] = explode('@', $account, 2);
            $visible = substr($local, 0, min(2, strlen($local)));
            return $visible . str_repeat('*', max(0, strlen($local) - 2)) . '@' . $domain;
        }

        $length = strlen($account);
        if ($length <= 4) return str_repeat('*', $length);

        return substr($account, 0, 2)
            . str_repeat('*', $length - 4)
            . substr($account, -2);
    }

    /**
     * Formate un montant en FCFA.
     */
    private function formatPrice($amount): string
    {
        return number_format((float) $amount, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Réponse erreur standardisée.
     */
    private function error(string $message, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}