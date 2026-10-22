<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Seller\ShipSellerOrderRequest;
use App\Http\Requests\Api\Seller\CancelSellerOrderRequest;
use App\Http\Resources\Api\Seller\SellerOrderResource;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Notifications\OrderStatusChangedNotification;
use App\Services\SellerOrderStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Controller : SellerOrderController
 *
 * Gère les commandes contenant les articles du vendeur.
 *
 * Sécurité :
 *   - Middleware : auth:sanctum + role:seller,admin + seller.active + ownership:order
 *   - Scope strict : seuls les items du vendeur connecté sont visibles
 *   - Policies : OrderPolicy appliquée sur chaque action
 *
 * Architecture :
 *   - Les actions vendeur modifient UNIQUEMENT order_items.seller_status
 *   - Le statut global orders.status est recalculé par SellerOrderStatusService
 */
class SellerOrderController extends Controller
{
    public function __construct(
        private readonly SellerOrderStatusService $statusService,
    ) {}

    // ============================================
    // INDEX — LISTE DES COMMANDES
    // ============================================

    /**
     * GET /api/v1/seller/orders
     *
     * Liste paginée des commandes contenant les items du vendeur.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);

            $query = Order::query()
                ->whereHas('items', fn ($q) => $q->where('seller_id', $sellerId))
                ->with([
                    'user:id,name,email',
                    'shippingAddress',
                    'items' => fn ($q) => $q->where('seller_id', $sellerId),
                ]);

            // ============================================
            // FILTRE : Statut global
            // ============================================
            if ($request->filled('status') && $request->input('status') !== 'all') {
                $query->where('status', $request->input('status'));
            }

            // ============================================
            // FILTRE : Statut vendeur (seller_status des items)
            // ============================================
            if ($request->filled('seller_status') && $request->input('seller_status') !== 'all') {
                $sellerStatus = $request->input('seller_status');
                $query->whereHas('items', function ($q) use ($sellerStatus, $sellerId) {
                    $q->where('seller_id', $sellerId)
                      ->where('seller_status', $sellerStatus);
                });
            }

            // ============================================
            // FILTRE : Recherche (numéro commande)
            // ============================================
            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%");
                });
            }

            // ============================================
            // FILTRE : Date (from / to)
            // ============================================
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }

            // ============================================
            // TRI
            // ============================================
            $sortBy = $request->input('sort_by', 'created_at');
            $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

            $allowedSorts = ['created_at', 'total', 'status'];
            if (!in_array($sortBy, $allowedSorts, true)) {
                $sortBy = 'created_at';
            }

            $query->orderBy($sortBy, $sortDir);

            // ============================================
            // PAGINATION
            // ============================================
            $perPage = min((int) $request->input('per_page', 15), 50);
            $orders = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Commandes récupérées avec succès.',
                'data'    => SellerOrderResource::collection(
                    $orders->map(fn ($order) => new SellerOrderResource($order, $sellerId, false))
                ),
                'pagination' => [
                    'total'        => $orders->total(),
                    'count'        => $orders->count(),
                    'per_page'     => $orders->perPage(),
                    'current_page' => $orders->currentPage(),
                    'last_page'    => $orders->lastPage(),
                    'has_more'     => $orders->hasMorePages(),
                ],
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerOrderController::index', ['error' => $e->getMessage()]);
            return $this->error('Impossible de récupérer les commandes : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // SHOW — DÉTAIL D'UNE COMMANDE
    // ============================================

    /**
     * GET /api/v1/seller/orders/{order}
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);

            // Vérifier que le vendeur a bien des items dans cette commande
            $hasSellerItems = $order->items()
                ->where('seller_id', $sellerId)
                ->exists();

            if (!$hasSellerItems) {
                return $this->error('Vous n\'avez aucun article dans cette commande.', Response::HTTP_NOT_FOUND);
            }

            $order->load([
                'user:id,name,email',
                'shippingAddress',
                'billingAddress',
                'items' => fn ($q) => $q->where('seller_id', $sellerId),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande récupérée avec succès.',
                'data'    => new SellerOrderResource($order, $sellerId, true),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error('SellerOrderController::show', ['error' => $e->getMessage()]);
            return $this->error('Impossible de récupérer la commande : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // ACCEPT — ACCEPTER LES ITEMS
    // ============================================

    /**
     * POST /api/v1/seller/orders/{order}/accept
     *
     * Accepte TOUS les items `to_accept` du vendeur.
     */
    public function accept(Request $request, Order $order): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);

            $itemsToAccept = $order->items()
                ->where('seller_id', $sellerId)
                ->where('seller_status', 'to_accept')
                ->get();

            if ($itemsToAccept->isEmpty()) {
                return $this->error('Aucun article à accepter dans cette commande.', Response::HTTP_CONFLICT);
            }

            DB::beginTransaction();

            $now = now();
            foreach ($itemsToAccept as $item) {
                $item->update([
                    'seller_status'      => 'accepted',
                    'seller_accepted_at' => $now,
                ]);
            }

            $this->statusService->recalculate($order);
            $this->notifyCustomer($order, 'accepted', $sellerId);

            DB::commit();

            Log::info('SellerOrder accepted', [
                'order_id'  => $order->id,
                'seller_id' => $sellerId,
                'items'     => $itemsToAccept->count(),
            ]);

            $order->refresh()->load([
                'user:id,name,email',
                'shippingAddress',
                'items' => fn ($q) => $q->where('seller_id', $sellerId),
            ]);

            return response()->json([
                'success' => true,
                'message' => $itemsToAccept->count() . ' article(s) accepté(s).',
                'data'    => new SellerOrderResource($order, $sellerId, true),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerOrderController::accept', ['error' => $e->getMessage()]);
            return $this->error('Impossible d\'accepter : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // PREPARE — PASSER EN PRÉPARATION
    // ============================================

    /**
     * POST /api/v1/seller/orders/{order}/prepare
     *
     * Passe les items `accepted` en `preparing`.
     */
    public function prepare(Request $request, Order $order): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);

            $itemsToPrepare = $order->items()
                ->where('seller_id', $sellerId)
                ->where('seller_status', 'accepted')
                ->get();

            if ($itemsToPrepare->isEmpty()) {
                return $this->error('Aucun article accepté à préparer.', Response::HTTP_CONFLICT);
            }

            DB::beginTransaction();

            foreach ($itemsToPrepare as $item) {
                $item->update(['seller_status' => 'preparing']);
            }

            $this->statusService->recalculate($order);
            $this->notifyCustomer($order, 'preparing', $sellerId);

            DB::commit();

            $order->refresh()->load([
                'user:id,name,email',
                'shippingAddress',
                'items' => fn ($q) => $q->where('seller_id', $sellerId),
            ]);

            return response()->json([
                'success' => true,
                'message' => $itemsToPrepare->count() . ' article(s) en préparation.',
                'data'    => new SellerOrderResource($order, $sellerId, true),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerOrderController::prepare', ['error' => $e->getMessage()]);
            return $this->error('Impossible de passer en préparation : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // SHIP — EXPÉDIER LES ITEMS
    // ============================================

    /**
     * POST /api/v1/seller/orders/{order}/ship
     *
     * Expédie les items `preparing` ou `ready_to_ship` en enregistrant
     * le numéro de suivi.
     */
    public function ship(ShipSellerOrderRequest $request, Order $order): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);

            $itemsToShip = $order->items()
                ->where('seller_id', $sellerId)
                ->whereIn('seller_status', ['preparing', 'ready_to_ship'])
                ->get();

            if ($itemsToShip->isEmpty()) {
                return $this->error('Aucun article à expédier.', Response::HTTP_CONFLICT);
            }

            $validated = $request->validated();
            $shippedAt = isset($validated['shipped_at'])
                ? \Carbon\Carbon::parse($validated['shipped_at'])
                : now();

            DB::beginTransaction();

            foreach ($itemsToShip as $item) {
                $item->update([
                    'seller_status'     => 'shipped',
                    'seller_shipped_at' => $shippedAt,
                ]);
            }

            // Mettre à jour le tracking global de la commande
            $order->update([
                'tracking_number' => $validated['tracking_number'],
                'shipping_method' => $validated['carrier'],
                'shipped_at'      => $shippedAt,
            ]);

            $this->statusService->recalculate($order);

            $this->notifyCustomer(
                $order,
                'shipped',
                $sellerId,
                [
                    'tracking_number' => $validated['tracking_number'],
                    'carrier'         => $validated['carrier'],
                    'tracking_url'    => $validated['tracking_url'] ?? null,
                ]
            );

            DB::commit();

            Log::info('SellerOrder shipped', [
                'order_id'        => $order->id,
                'seller_id'       => $sellerId,
                'tracking_number' => $validated['tracking_number'],
            ]);

            $order->refresh()->load([
                'user:id,name,email',
                'shippingAddress',
                'items' => fn ($q) => $q->where('seller_id', $sellerId),
            ]);

            return response()->json([
                'success' => true,
                'message' => $itemsToShip->count() . ' article(s) expédié(s).',
                'data'    => new SellerOrderResource($order, $sellerId, true),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerOrderController::ship', ['error' => $e->getMessage()]);
            return $this->error('Impossible d\'expédier : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // CANCEL — ANNULER LES ITEMS
    // ============================================

    /**
     * POST /api/v1/seller/orders/{order}/cancel
     *
     * Annule les items annulables du vendeur.
     */
    public function cancel(CancelSellerOrderRequest $request, Order $order): JsonResponse
    {
        try {
            $sellerId = $this->resolveSellerId($request);
            $itemsToCancel = $request->getItemsToCancel();

            if ($itemsToCancel->isEmpty()) {
                return $this->error('Aucun article à annuler.', Response::HTTP_CONFLICT);
            }

            $validated = $request->validated();
            $reason = $validated['reason'];
            $reasonLabel = $request->getReasonLabel();
            $restock = $request->shouldRestock();

            DB::beginTransaction();

            foreach ($itemsToCancel as $item) {
                $item->update([
                    'seller_status'   => 'cancelled',
                    'item_status'     => 'cancelled',
                    'return_reason'   => $reasonLabel,
                ]);

                // Réintégrer le stock si demandé
                if ($restock) {
                    $product = $item->product;
                    if ($product) {
                        $product->increment('stock', $item->quantity);
                    }
                }
            }

            $this->statusService->recalculate($order);

            $this->notifyCustomer(
                $order,
                'cancelled',
                $sellerId,
                [
                    'reason'       => $reasonLabel,
                    'items_count'  => $itemsToCancel->count(),
                ]
            );

            DB::commit();

            Log::info('SellerOrder cancelled', [
                'order_id'  => $order->id,
                'seller_id' => $sellerId,
                'reason'    => $reason,
                'items'     => $itemsToCancel->count(),
            ]);

            $order->refresh()->load([
                'user:id,name,email',
                'shippingAddress',
                'items' => fn ($q) => $q->where('seller_id', $sellerId),
            ]);

            return response()->json([
                'success' => true,
                'message' => $itemsToCancel->count() . ' article(s) annulé(s).',
                'data'    => new SellerOrderResource($order, $sellerId, true),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('SellerOrderController::cancel', ['error' => $e->getMessage()]);
            return $this->error('Impossible d\'annuler : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ============================================
    // HELPERS PRIVÉS
    // ============================================

    /**
     * Résout l'ID du vendeur cible.
     */
    private function resolveSellerId(Request $request): int
    {
        $user = $request->user();

        if ($user->isAdmin() && $request->has('seller_id')) {
            return (int) $request->input('seller_id');
        }

        return $user->id;
    }

    /**
     * Envoie une notification au client.
     */
    private function notifyCustomer(
        Order $order,
        string $event,
        int $sellerId,
        array $extra = []
    ): void {
        try {
            $customer = $order->user;

            if (!$customer) {
                return;
            }

            Notification::send(
                $customer,
                new OrderStatusChangedNotification(
                    order: $order,
                    event: $event,
                    sellerId: $sellerId,
                    extra: $extra,
                )
            );
        } catch (Throwable $e) {
            Log::warning('Notification client échouée', [
                'order_id' => $order->id,
                'event'    => $event,
                'error'    => $e->getMessage(),
            ]);
        }
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