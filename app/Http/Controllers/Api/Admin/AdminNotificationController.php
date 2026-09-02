<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Support\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminNotificationController extends Controller
{
    /**
     * Récupérer la liste paginée des notifications.
     *
     * Endpoint : GET /api/v1/admin/notifications
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $page = $request->query('page', 1);

            $query = Notification::orderByDesc('created_at');

            // Filtres
            if ($request->has('type') && !empty($request->query('type'))) {
                $query->where('type', $request->query('type'));
            }

            if ($request->has('priority') && !empty($request->query('priority'))) {
                $query->where('priority', $request->query('priority'));
            }

            if ($request->has('is_read')) {
                $query->where('is_read', $request->query('is_read') == 'true');
            }

            if ($request->has('is_archived')) {
                $query->where('is_archived', $request->query('is_archived') == 'true');
            }

            if ($request->has('search') && !empty($request->query('search'))) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('body', 'LIKE', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->query('sort_by', 'created_at');
            $sortDirection = $request->query('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $notifications = $query->paginate($perPage, ['*'], 'page', $page);

            $data = $notifications->map(function ($notification) {
                return $this->formatNotification($notification);
            });

            return response()->json([
                'success' => true,
                'message' => 'Notifications récupérées avec succès',
                'data' => $data,
                'pagination' => [
                    'total' => $notifications->total(),
                    'count' => $notifications->count(),
                    'per_page' => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'has_more' => $notifications->hasMorePages(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les détails d'une notification.
     *
     * Endpoint : GET /api/v1/admin/notifications/{id}
     */
    public function show($id)
    {
        try {
            $notification = Notification::find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification introuvable',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification récupérée avec succès',
                'data' => $this->formatNotification($notification),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les notifications non lues.
     *
     * Endpoint : GET /api/v1/admin/notifications/unread
     */
    public function unread(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);

            $notifications = Notification::where('is_read', false)
                ->where('is_archived', false)
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Notifications non lues récupérées',
                'data' => $notifications->items(),
                'pagination' => [
                    'total' => $notifications->total(),
                    'count' => $notifications->count(),
                    'per_page' => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'has_more' => $notifications->hasMorePages(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications non lues',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les notifications lues.
     *
     * Endpoint : GET /api/v1/admin/notifications/read
     */
    public function read(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);

            $notifications = Notification::where('is_read', true)
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Notifications lues récupérées',
                'data' => $notifications->items(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications lues',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les notifications archivées.
     *
     * Endpoint : GET /api/v1/admin/notifications/archived
     */
    public function archived(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);

            $notifications = Notification::where('is_archived', true)
                ->orderByDesc('archived_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Notifications archivées récupérées',
                'data' => $notifications->items(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications archivées',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les notifications urgentes.
     *
     * Endpoint : GET /api/v1/admin/notifications/urgent
     */
    public function urgent()
    {
        try {
            $notifications = Notification::where('priority', 'urgent')
                ->where('is_read', false)
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Notifications urgentes récupérées',
                'data' => $notifications->map(function ($n) {
                    return $this->formatNotification($n);
                }),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications urgentes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les notifications de haute priorité.
     *
     * Endpoint : GET /api/v1/admin/notifications/high-priority
     */
    public function highPriority()
    {
        try {
            $notifications = Notification::whereIn('priority', ['high', 'urgent'])
                ->where('is_read', false)
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Notifications prioritaires récupérées',
                'data' => $notifications->map(function ($n) {
                    return $this->formatNotification($n);
                }),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications prioritaires',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les notifications par type.
     *
     * Endpoint : GET /api/v1/admin/notifications/by-type/{type}
     */
    public function byType($type)
    {
        try {
            $notifications = Notification::where('type', $type)
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Notifications par type récupérées',
                'data' => $notifications->map(function ($n) {
                    return $this->formatNotification($n);
                }),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications par type',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les notifications récentes.
     *
     * Endpoint : GET /api/v1/admin/notifications/recent
     */
    public function recent(Request $request)
    {
        try {
            $limit = $request->query('limit', 20);

            $notifications = Notification::orderByDesc('created_at')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Notifications récentes récupérées',
                'data' => $notifications->map(function ($n) {
                    return $this->formatNotification($n);
                }),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications récentes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marquer une notification comme lue.
     *
     * Endpoint : POST /api/v1/admin/notifications/{id}/read
     */
    public function markAsRead($id)
    {
        try {
            $notification = Notification::find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification introuvable',
                ], 404);
            }

            $notification->is_read = true;
            $notification->read_at = now();
            $notification->save();

            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme lue',
                'data' => $this->formatNotification($notification),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage comme lu',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marquer une notification comme non lue.
     *
     * Endpoint : POST /api/v1/admin/notifications/{id}/unread
     */
    public function markAsUnread($id)
    {
        try {
            $notification = Notification::find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification introuvable',
                ], 404);
            }

            $notification->is_read = false;
            $notification->read_at = null;
            $notification->save();

            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme non lue',
                'data' => $this->formatNotification($notification),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage comme non lu',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marquer toutes les notifications comme lues.
     *
     * Endpoint : POST /api/v1/admin/notifications/mark-all-read
     */
    public function markAllAsRead()
    {
        try {
            $count = Notification::where('is_read', false)->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "$count notifications marquées comme lues",
                'data' => ['count' => (int) $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage en masse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marquer plusieurs notifications comme lues.
     *
     * Endpoint : POST /api/v1/admin/notifications/bulk-read
     */
    public function bulkMarkAsRead(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune notification sélectionnée',
                ], 422);
            }

            $count = Notification::whereIn('id', $ids)->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "$count notifications marquées comme lues",
                'data' => ['count' => (int) $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage en masse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Archiver une notification.
     *
     * Endpoint : POST /api/v1/admin/notifications/{id}/archive
     */
    public function archive($id)
    {
        try {
            $notification = Notification::find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification introuvable',
                ], 404);
            }

            $notification->is_archived = true;
            $notification->archived_at = now();
            $notification->save();

            return response()->json([
                'success' => true,
                'message' => 'Notification archivée',
                'data' => $this->formatNotification($notification),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'archivage',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Désarchiver une notification.
     *
     * Endpoint : POST /api/v1/admin/notifications/{id}/unarchive
     */
    public function unarchive($id)
    {
        try {
            $notification = Notification::find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification introuvable',
                ], 404);
            }

            $notification->is_archived = false;
            $notification->archived_at = null;
            $notification->save();

            return response()->json([
                'success' => true,
                'message' => 'Notification désarchivée',
                'data' => $this->formatNotification($notification),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du désarchivage',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Archiver toutes les notifications lues.
     *
     * Endpoint : POST /api/v1/admin/notifications/archive-all-read
     */
    public function archiveAllRead()
    {
        try {
            $count = Notification::where('is_read', true)
                ->where('is_archived', false)
                ->update([
                    'is_archived' => true,
                    'archived_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => "$count notifications archivées",
                'data' => ['count' => (int) $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'archivage en masse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Archiver plusieurs notifications.
     *
     * Endpoint : POST /api/v1/admin/notifications/bulk-archive
     */
    public function bulkArchive(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune notification sélectionnée',
                ], 422);
            }

            $count = Notification::whereIn('id', $ids)->update([
                'is_archived' => true,
                'archived_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "$count notifications archivées",
                'data' => ['count' => (int) $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'archivage en masse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une notification.
     *
     * Endpoint : DELETE /api/v1/admin/notifications/{id}
     */
    public function destroy($id)
    {
        try {
            $notification = Notification::find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification introuvable',
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification supprimée avec succès',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer toutes les notifications lues.
     *
     * Endpoint : DELETE /api/v1/admin/notifications/delete-all-read
     */
    public function deleteAllRead()
    {
        try {
            $count = Notification::where('is_read', true)->delete();

            return response()->json([
                'success' => true,
                'message' => "$count notifications supprimées",
                'data' => ['count' => (int) $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression en masse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer toutes les notifications archivées.
     *
     * Endpoint : DELETE /api/v1/admin/notifications/delete-all-archived
     */
    public function deleteAllArchived()
    {
        try {
            $count = Notification::where('is_archived', true)->delete();

            return response()->json([
                'success' => true,
                'message' => "$count notifications supprimées",
                'data' => ['count' => (int) $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression en masse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer toutes les notifications.
     *
     * Endpoint : DELETE /api/v1/admin/notifications/clear-all
     */
    public function clearAll()
    {
        try {
            Notification::query()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Toutes les notifications ont été supprimées',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression totale',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer plusieurs notifications.
     *
     * Endpoint : POST /api/v1/admin/notifications/bulk-delete
     */
    public function bulkDelete(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune notification sélectionnée',
                ], 422);
            }

            $count = Notification::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "$count notifications supprimées",
                'data' => ['count' => (int) $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression en masse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les statistiques des notifications.
     *
     * Endpoint : GET /api/v1/admin/notifications/stats/count
     */
    public function statsCount()
    {
        try {
            $total = Notification::count();
            $unread = Notification::where('is_read', false)->where('is_archived', false)->count();
            $read = Notification::where('is_read', true)->count();
            $archived = Notification::where('is_archived', true)->count();

            return response()->json([
                'success' => true,
                'message' => 'Statistiques récupérées',
                'data' => [
                    'total' => (int) $total,
                    'unread' => (int) $unread,
                    'read' => (int) $read,
                    'archived' => (int) $archived,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer le nombre de notifications non lues.
     *
     * Endpoint : GET /api/v1/admin/notifications/stats/unread-count
     */
    public function statsUnreadCount()
    {
        try {
            $count = Notification::where('is_read', false)
                ->where('is_archived', false)
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Nombre de notifications non lues récupéré',
                'data' => ['count' => (int) $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du nombre',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les notifications par type.
     *
     * Endpoint : GET /api/v1/admin/notifications/stats/by-type
     */
    public function statsByType()
    {
        try {
            $types = Notification::select('type', DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Notifications par type récupérées',
                'data' => $types,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération par type',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les notifications par priorité.
     *
     * Endpoint : GET /api/v1/admin/notifications/stats/by-priority
     */
    public function statsByPriority()
    {
        try {
            $priorities = Notification::select('priority', DB::raw('COUNT(*) as count'))
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Notifications par priorité récupérées',
                'data' => $priorities,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération par priorité',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Formater une notification pour la réponse API.
     */
    private function formatNotification(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'body' => $notification->body,
            'type' => $notification->type,
            'priority' => $notification->priority,
            'image' => $notification->image,
            'icon' => $notification->icon,
            'is_read' => (bool) $notification->is_read,
            'read_at' => $notification->read_at ? $notification->read_at->toIso8601String() : null,
            'is_archived' => (bool) $notification->is_archived,
            'archived_at' => $notification->archived_at ? $notification->archived_at->toIso8601String() : null,
            'target_type' => $notification->target_type,
            'target_id' => $notification->target_id,
            'target_url' => $notification->target_url,
            'created_at' => $notification->created_at->toIso8601String(),
            'updated_at' => $notification->updated_at->toIso8601String(),
        ];
    }
}