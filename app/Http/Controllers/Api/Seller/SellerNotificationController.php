<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Seller\SellerNotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Controller : SellerNotificationController
 *
 * Gère les notifications du vendeur connecté.
 *
 * Utilise la table `notifications` de Laravel (canal database).
 *
 * ⚠️ RÈGLES DE SÉCURITÉ CRITIQUES :
 *   - Scope strict : notifiable_id = auth()->id() ET notifiable_type = User
 *   - Aucune notification d'un autre utilisateur n'est exposée
 *   - Toutes les opérations (read, readAll) sont filtrées par user_id
 *
 * Actions :
 *   - index()       : Liste paginée avec filtres (all, unread)
 *   - read()        : Marquer une notification comme lue
 *   - readAll()     : Marquer toutes les notifications comme lues
 *   - unreadCount() : Compteur de notifications non lues (badge)
 *
 * @package App\Http\Controllers\Api\Seller
 */
class SellerNotificationController extends Controller
{
    // ============================================
    // INDEX — LISTE DES NOTIFICATIONS
    // ============================================

    /**
     * GET /api/v1/seller/notifications
     *
     * Liste paginée des notifications du vendeur.
     *
     * Query params :
     *   - filter   : all | unread | read
     *   - type     : new_order | new_review | low_stock | payout_received | kyc_validated
     *   - per_page : 20 par défaut, max 50
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // ============================================
            // REQUÊTE DE BASE (scope strict)
            // ============================================
            $query = $user->notifications()->getQuery();

            // ============================================
            // FILTRE : Lecture
            // ============================================
            $filter = $request->input('filter', 'all');
            if ($filter === 'unread') {
                $query->whereNull('read_at');
            } elseif ($filter === 'read') {
                $query->whereNotNull('read_at');
            }

            // ============================================
            // FILTRE : Type (via JSON path)
            // ============================================
            if ($request->filled('type')) {
                $type = $request->input('type');
                $query->where(function ($q) use ($type) {
                    $q->where('data->type', $type)
                      ->orWhere('data->event', $type);
                });
            }

            // ============================================
            // TRI & PAGINATION
            // ============================================
            $perPage = min((int) $request->input('per_page', 20), 50);
            $notifications = $query->latest()->paginate($perPage);

            // ============================================
            // COMPTEURS
            // ============================================
            $unreadCount = $user->unreadNotifications()->count();
            $totalCount = $user->notifications()->count();

            return response()->json([
                'success' => true,
                'message' => 'Notifications récupérées avec succès.',
                'data'    => SellerNotificationResource::collection($notifications),
                'pagination' => [
                    'total'        => $notifications->total(),
                    'count'        => $notifications->count(),
                    'per_page'     => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page'    => $notifications->lastPage(),
                    'has_more'     => $notifications->hasMorePages(),
                ],
                'counts' => [
                    'total'  => $totalCount,
                    'unread' => $unreadCount,
                    'read'   => $totalCount - $unreadCount,
                ],
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerNotificationController::index', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de récupérer les notifications : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // READ — MARQUER UNE NOTIFICATION COMME LUE
    // ============================================

    /**
     * POST /api/v1/seller/notifications/{id}/read
     *
     * Marque une notification spécifique comme lue.
     *
     * ⚠️ Sécurité : vérifie que la notification appartient bien au user.
     */
    public function read(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();

            // Récupérer UNIQUEMENT les notifications du user connecté
            $notification = $user->notifications()
                ->where('id', $id)
                ->first();

            if (!$notification) {
                return $this->error(
                    'Notification introuvable.',
                    Response::HTTP_NOT_FOUND
                );
            }

            // Marquer comme lue (si pas déjà lu)
            if ($notification->read_at === null) {
                $notification->markAsRead();
            }

            $unreadCount = $user->unreadNotifications()->count();

            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme lue.',
                'unread_count' => $unreadCount,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerNotificationController::read', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de marquer la notification : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // READ ALL — TOUT MARQUER COMME LU
    // ============================================

    /**
     * POST /api/v1/seller/notifications/read-all
     *
     * Marque toutes les notifications du vendeur comme lues.
     */
    public function readAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Compter avant
            $countBefore = $user->unreadNotifications()->count();

            // Marquer tout comme lu
            $user->unreadNotifications->markAsRead();

            return response()->json([
                'success' => true,
                'message' => $countBefore > 0
                    ? "{$countBefore} notification(s) marquée(s) comme lue(s)."
                    : 'Aucune notification à marquer.',
                'marked_count' => $countBefore,
                'unread_count' => 0,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerNotificationController::readAll', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de marquer les notifications : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // UNREAD COUNT — COMPTEUR BADGE
    // ============================================

    /**
     * GET /api/v1/seller/notifications/unread-count
     *
     * Retourne le nombre de notifications non lues.
     * Utilisé pour le badge de l'AppBar.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $unreadCount = $user->unreadNotifications()->count();

            return response()->json([
                'success'      => true,
                'unread_count' => $unreadCount,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerNotificationController::unreadCount', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de compter les notifications : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // DELETE — SUPPRIMER UNE NOTIFICATION (bonus)
    // ============================================

    /**
     * DELETE /api/v1/seller/notifications/{id}
     *
     * Supprime une notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();

            $notification = $user->notifications()
                ->where('id', $id)
                ->first();

            if (!$notification) {
                return $this->error(
                    'Notification introuvable.',
                    Response::HTTP_NOT_FOUND
                );
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification supprimée.',
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerNotificationController::destroy', ['error' => $e->getMessage()]);
            return $this->error(
                'Impossible de supprimer la notification : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ============================================
    // HELPERS
    // ============================================

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