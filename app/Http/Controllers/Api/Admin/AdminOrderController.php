<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderStatusHistory;
use App\Models\Order\OrderTracking;
use App\Models\Payment\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
    /**
     * Récupérer la liste paginée des commandes.
     *
     * Endpoint : GET /api/v1/admin/orders
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $page = $request->query('page', 1);

            $query = Order::with(['user', 'items', 'payments'])
                ->orderByDesc('created_at');

            // Filtres
            if ($request->has('status') && !empty($request->query('status'))) {
                $query->where('status', $request->query('status'));
            }

            if ($request->has('payment_status') && !empty($request->query('payment_status'))) {
                $query->where('payment_status', $request->query('payment_status'));
            }

            if ($request->has('user_id')) {
                $query->where('user_id', $request->query('user_id'));
            }

            if ($request->has('search') && !empty($request->query('search'))) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'LIKE', "%{$search}%")
                      ->orWhereHas('user', function ($uq) use ($search) {
                          $uq->where('name', 'LIKE', "%{$search}%")
                             ->orWhere('email', 'LIKE', "%{$search}%");
                      });
                });
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [
                    $request->query('start_date') . ' 00:00:00',
                    $request->query('end_date') . ' 23:59:59',
                ]);
            }

            // Tri
            $sortBy = $request->query('sort_by', 'created_at');
            $sortDirection = $request->query('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $orders = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'message' => 'Commandes récupérées avec succès',
                'data' => $orders->items(),
                'pagination' => [
                    'total' => $orders->total(),
                    'count' => $orders->count(),
                    'per_page' => $orders->perPage(),
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'has_more' => $orders->hasMorePages(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les détails d'une commande.
     *
     * Endpoint : GET /api/v1/admin/orders/{id}
     */
    public function show($id)
    {
        try {
            $order = Order::with(['user', 'items.product', 'payments', 'statusHistory', 'trackings'])
                ->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Commande récupérée avec succès',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer une commande par son numéro.
     *
     * Endpoint : GET /api/v1/admin/orders/by-number/{orderNumber}
     */
    public function showByNumber($orderNumber)
    {
        try {
            $order = Order::with(['user', 'items', 'payments'])
                ->where('order_number', $orderNumber)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Commande récupérée avec succès',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les commandes en attente.
     *
     * Endpoint : GET /api/v1/admin/orders/pending
     */
    public function pending(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $orders = Order::with('user')
                ->where('status', 'pending')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Commandes en attente récupérées',
                'data' => $orders->items(),
                'pagination' => [
                    'total' => $orders->total(),
                    'count' => $orders->count(),
                    'per_page' => $orders->perPage(),
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'has_more' => $orders->hasMorePages(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes en attente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les commandes confirmées.
     *
     * Endpoint : GET /api/v1/admin/orders/confirmed
     */
    public function confirmed(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $orders = Order::with('user')
                ->where('status', 'confirmed')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Commandes confirmées récupérées',
                'data' => $orders->items(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes confirmées',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les commandes en traitement.
     *
     * Endpoint : GET /api/v1/admin/orders/processing
     */
    public function processing(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $orders = Order::with('user')
                ->where('status', 'processing')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Commandes en traitement récupérées',
                'data' => $orders->items(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes en traitement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les commandes expédiées.
     *
     * Endpoint : GET /api/v1/admin/orders/shipped
     */
    public function shipped(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $orders = Order::with('user')
                ->where('status', 'shipped')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Commandes expédiées récupérées',
                'data' => $orders->items(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes expédiées',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les commandes livrées.
     *
     * Endpoint : GET /api/v1/admin/orders/delivered
     */
    public function delivered(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $orders = Order::with('user')
                ->where('status', 'delivered')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Commandes livrées récupérées',
                'data' => $orders->items(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes livrées',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les commandes annulées.
     *
     * Endpoint : GET /api/v1/admin/orders/cancelled
     */
    public function cancelled(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $orders = Order::with('user')
                ->where('status', 'cancelled')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Commandes annulées récupérées',
                'data' => $orders->items(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes annulées',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les commandes d'un utilisateur.
     *
     * Endpoint : GET /api/v1/admin/orders/by-user/{userId}
     */
    public function byUser($userId)
    {
        try {
            $orders = Order::with('items')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Commandes de l\'utilisateur récupérées',
                'data' => $orders,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirmer une commande.
     *
     * Endpoint : POST /api/v1/admin/orders/{id}/confirm
     */
    public function confirm($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            $oldStatus = $order->status;
            $order->status = 'confirmed';
            $order->save();

            // Enregistrer dans l'historique
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'confirmed',
                'note' => 'Commande confirmée par l\'administrateur',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande confirmée avec succès',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marquer une commande comme en traitement.
     *
     * Endpoint : POST /api/v1/admin/orders/{id}/process
     */
    public function process($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            $oldStatus = $order->status;
            $order->status = 'processing';
            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'processing',
                'note' => 'Commande en cours de traitement',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande en traitement',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Expédier une commande.
     *
     * Endpoint : POST /api/v1/admin/orders/{id}/ship
     */
    public function ship(Request $request, $id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            $oldStatus = $order->status;
            $order->status = 'shipped';
            $order->tracking_number = $request->input('tracking_number');
            $order->shipped_at = now();
            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'shipped',
                'note' => 'Commande expédiée - ' . ($request->input('tracking_number') ?? 'Sans numéro de suivi'),
            ]);

            // Créer le tracking
            if ($request->input('tracking_number')) {
                OrderTracking::create([
                    'order_id' => $order->id,
                    'tracking_number' => $request->input('tracking_number'),
                    'carrier' => $request->input('carrier'),
                    'status' => 'picked_up',
                    'current_location' => $request->input('current_location', 'Entrepôt'),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Commande expédiée avec succès',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'expédition de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Livrer une commande.
     *
     * Endpoint : POST /api/v1/admin/orders/{id}/deliver
     */
    public function deliver($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            $oldStatus = $order->status;
            $order->status = 'delivered';
            $order->delivered_at = now();
            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'delivered',
                'note' => 'Commande livrée',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande livrée avec succès',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la livraison de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Annuler une commande.
     *
     * Endpoint : POST /api/v1/admin/orders/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            $oldStatus = $order->status;
            $order->status = 'cancelled';
            $order->cancelled_at = now();
            $order->cancellation_reason = $request->input('reason', 'Annulée par l\'administrateur');
            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'cancelled',
                'note' => $request->input('reason', 'Commande annulée'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande annulée avec succès',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rembourser une commande.
     *
     * Endpoint : POST /api/v1/admin/orders/{id}/refund
     */
    public function refund($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            $oldStatus = $order->status;
            $order->status = 'refunded';
            $order->payment_status = 'refunded';
            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'refunded',
                'note' => 'Commande remboursée',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande remboursée avec succès',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du remboursement de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marquer une commande comme retournée.
     *
     * Endpoint : POST /api/v1/admin/orders/{id}/return
     */
    public function returnOrder(Request $request, $id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            $oldStatus = $order->status;
            $order->status = 'returned';
            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'returned',
                'note' => $request->input('reason', 'Commande retournée'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande retournée',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retour de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer l'historique des statuts d'une commande.
     *
     * Endpoint : GET /api/v1/admin/orders/{id}/history
     */
    public function history($id)
    {
        try {
            $history = OrderStatusHistory::where('order_id', $id)
                ->with('user')
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'old_status' => $item->old_status,
                        'new_status' => $item->new_status,
                        'note' => $item->note,
                        'user' => $item->user ? $item->user->name : 'Système',
                        'created_at' => $item->created_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Historique récupéré',
                'data' => $history,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer le suivi de livraison d'une commande.
     *
     * Endpoint : GET /api/v1/admin/orders/{id}/tracking
     */
    public function tracking($id)
    {
        try {
            $tracking = OrderTracking::where('order_id', $id)
                ->orderByDesc('created_at')
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Suivi récupéré',
                'data' => $tracking,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du suivi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marquer le paiement comme reçu.
     *
     * Endpoint : POST /api/v1/admin/orders/{id}/payment/paid
     */
    public function markPaymentAsPaid($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            $order->payment_status = 'paid';
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Paiement marqué comme reçu',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage du paiement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marquer le paiement comme échoué.
     *
     * Endpoint : POST /api/v1/admin/orders/{id}/payment/failed
     */
    public function markPaymentAsFailed($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            $order->payment_status = 'failed';
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Paiement marqué comme échoué',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage du paiement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marquer le paiement comme remboursé.
     *
     * Endpoint : POST /api/v1/admin/orders/{id}/payment/refunded
     */
    public function markPaymentAsRefunded($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            $order->payment_status = 'refunded';
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Paiement marqué comme remboursé',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage du paiement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ajouter une note admin à une commande.
     *
     * Endpoint : POST /api/v1/admin/orders/{id}/notes
     */
    public function addNote(Request $request, $id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            $order->admin_note = $request->input('note');
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Note ajoutée avec succès',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de la note',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les notes admin d'une commande.
     *
     * Endpoint : GET /api/v1/admin/orders/{id}/notes
     */
    public function notes($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notes récupérées',
                'data' => [
                    'admin_note' => $order->admin_note,
                    'customer_note' => $order->customer_note,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les statistiques des commandes.
     *
     * Endpoint : GET /api/v1/admin/orders/stats/count
     */
    public function statsCount()
    {
        try {
            $totalOrders = Order::count();
            $pendingOrders = Order::where('status', 'pending')->count();
            $deliveredOrders = Order::where('status', 'delivered')->count();
            $cancelledOrders = Order::where('status', 'cancelled')->count();

            return response()->json([
                'success' => true,
                'message' => 'Statistiques récupérées',
                'data' => [
                    'total' => (int) $totalOrders,
                    'pending' => (int) $pendingOrders,
                    'delivered' => (int) $deliveredOrders,
                    'cancelled' => (int) $cancelledOrders,
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
     * Récupérer les commandes par statut.
     *
     * Endpoint : GET /api/v1/admin/orders/stats/by-status
     */
    public function statsByStatus()
    {
        try {
            $orders = Order::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Commandes par statut récupérées',
                'data' => $orders,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes par statut',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer le revenu total.
     *
     * Endpoint : GET /api/v1/admin/orders/stats/total-revenue
     */
    public function statsTotalRevenue()
    {
        try {
            $totalRevenue = Order::where('payment_status', 'paid')->sum('total');

            return response()->json([
                'success' => true,
                'message' => 'Revenu total récupéré',
                'data' => ['total' => (float) $totalRevenue],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du revenu total',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une commande (soft delete).
     *
     * Endpoint : DELETE /api/v1/admin/orders/{id}
     */
    public function destroy($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Commande supprimée avec succès',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restaurer une commande supprimée.
     *
     * Endpoint : POST /api/v1/admin/orders/{id}/restore
     */
    public function restore($id)
    {
        try {
            $order = Order::withTrashed()->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable',
                ], 404);
            }

            $order->restore();

            return response()->json([
                'success' => true,
                'message' => 'Commande restaurée avec succès',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}