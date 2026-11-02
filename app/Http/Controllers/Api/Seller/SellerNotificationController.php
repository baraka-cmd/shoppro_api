<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Seller\SellerNotificationResource;
use App\Models\Support\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SellerNotificationController extends Controller
{
    /** List notifications owned by the authenticated seller. */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $base = Notification::query()->where('user_id', $userId);
            $query = clone $base;

            match ($request->input('filter', 'all')) {
                'unread' => $query->where('is_read', false),
                'read' => $query->where('is_read', true),
                default => null,
            };

            if ($request->filled('type')) {
                $requestedType = $request->string('type')->toString();
                $storedTypes = match ($requestedType) {
                    'new_order', 'order_created', 'order_status_changed' => ['order'],
                    'new_review', 'review_created' => ['review'],
                    'low_stock', 'out_of_stock' => ['stock_alert'],
                    'payout_received', 'payout_processed', 'payout_completed' => ['payout'],
                    'general' => ['system'],
                    default => [$requestedType],
                };
                $query->whereIn('type', $storedTypes);
            }

            $perPage = max(1, min((int) $request->input('per_page', 20), 50));
            $notifications = $query->latest()->paginate($perPage);
            $total = (clone $base)->count();
            $unread = (clone $base)->where('is_read', false)->count();

            return response()->json([
                'success' => true,
                'message' => 'Notifications récupérées avec succès.',
                'data' => SellerNotificationResource::collection($notifications),
                'pagination' => [
                    'total' => $notifications->total(),
                    'count' => $notifications->count(),
                    'per_page' => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'has_more' => $notifications->hasMorePages(),
                ],
                'counts' => ['total' => $total, 'unread' => $unread, 'read' => $total - $unread],
            ]);
        } catch (Throwable $e) {
            Log::error('SellerNotificationController::index', ['error' => $e->getMessage()]);
            return $this->error('Impossible de récupérer les notifications.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Mark one seller-owned notification as read. */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        try {
            $notification = $this->findForUser($request, $id);
            if (!$notification) return $this->error('Notification introuvable.', Response::HTTP_NOT_FOUND);

            $notification->markAsRead();
            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme lue.',
                'unread_count' => Notification::where('user_id', $request->user()->id)->where('is_read', false)->count(),
            ]);
        } catch (Throwable $e) {
            Log::error('SellerNotificationController::markAsRead', ['error' => $e->getMessage()]);
            return $this->error('Impossible de marquer la notification comme lue.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Mark one seller-owned notification as unread. */
    public function markAsUnread(Request $request, int $id): JsonResponse
    {
        try {
            $notification = $this->findForUser($request, $id);
            if (!$notification) return $this->error('Notification introuvable.', Response::HTTP_NOT_FOUND);

            $notification->update(['is_read' => false, 'read_at' => null]);
            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme non lue.',
                'unread_count' => Notification::where('user_id', $request->user()->id)->where('is_read', false)->count(),
            ]);
        } catch (Throwable $e) {
            Log::error('SellerNotificationController::markAsUnread', ['error' => $e->getMessage()]);
            return $this->error('Impossible de marquer la notification comme non lue.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Mark all notifications belonging to this seller as read. */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $count = Notification::where('user_id', $request->user()->id)->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now(), 'updated_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => $count > 0 ? "$count notification(s) marquée(s) comme lue(s)." : 'Aucune notification à marquer.',
                'marked_count' => $count,
                'unread_count' => 0,
            ]);
        } catch (Throwable $e) {
            Log::error('SellerNotificationController::markAllAsRead', ['error' => $e->getMessage()]);
            return $this->error('Impossible de marquer les notifications comme lues.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Return the seller's unread count. */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)->where('is_read', false)->count();
        return response()->json(['success' => true, 'unread_count' => $count]);
    }

    /** Soft-delete a seller-owned notification. */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $notification = $this->findForUser($request, $id);
        if (!$notification) return $this->error('Notification introuvable.', Response::HTTP_NOT_FOUND);

        $notification->delete();
        return response()->json(['success' => true, 'message' => 'Notification supprimée.']);
    }

    private function findForUser(Request $request, int $id): ?Notification
    {
        return Notification::where('user_id', $request->user()->id)->whereKey($id)->first();
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
