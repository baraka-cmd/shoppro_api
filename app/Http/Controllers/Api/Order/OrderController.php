<?php

namespace App\Http\Controllers\Api\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderStatusHistory;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    // ============================================
    // LISTE DES COMMANDES DU CLIENT
    // ============================================
    /**
     * Récupérer la liste paginée des commandes de l'utilisateur connecté.
     *
     * Endpoint : GET /api/v1/orders
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = (int) $request->query('per_page', 10);
            $perPage = min(max($perPage, 1), 50);

            $query = Order::with(['items', 'payments'])
                ->where('user_id', $user->id);

            // Filtre par statut
            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }

            // Filtre par statut de paiement
            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->query('payment_status'));
            }

            $query->orderByDesc('created_at');

            $orders = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Commandes récupérées avec succès.',
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
                'message' => 'Erreur lors de la récupération des commandes.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // DÉTAILS D'UNE COMMANDE
    // ============================================
    /**
     * Récupérer les détails d'une commande de l'utilisateur connecté.
     *
     * Endpoint : GET /api/v1/orders/{id}
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $user = Auth::user();

            $order = Order::with([
                'items.product',
                'items.variant',
                'shippingAddress',
                'billingAddress',
                'payments',
                'statusHistory',
                'trackings',
            ])
                ->where('user_id', $user->id)
                ->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Commande récupérée avec succès.',
                'data' => $order,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la commande.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // CRÉER UNE COMMANDE (CHECKOUT)
    // ============================================
    /**
     * Créer une nouvelle commande.
     *
     * Endpoint : POST /api/v1/orders
     *
     * Body attendu :
     * {
     *   "shipping_address_id": 1,
     *   "billing_address_id": 1,
     *   "shipping_method": "standard",
     *   "payment_method": "cash_on_delivery",
     *   "coupon_code": "PROMO10",
     *   "customer_note": "Livrer avant 18h",
     *   "items": [
     *     { "product_id": 1, "quantity": 2 },
     *     { "product_id": 3, "quantity": 1, "variant_id": 5 }
     *   ]
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            // ============================================
            // 1. VALIDATION
            // ============================================
            $validator = Validator::make($request->all(), [
                'shipping_address_id' => 'required|integer|exists:user_addresses,id',
                'billing_address_id' => 'nullable|integer|exists:user_addresses,id',
                'shipping_method' => 'nullable|string|max:100',
                'payment_method' => 'nullable|string|max:50',
                'coupon_code' => 'nullable|string|max:50',
                'customer_note' => 'nullable|string|max:500',

                // Items (obligatoire maintenant)
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
                'items.*.quantity' => 'required|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // ============================================
            // 2. VÉRIFIER L'ADRESSE APPARTIENT AU USER
            // ============================================
            $shippingAddress = \App\Models\User\UserAddress::where('id', $request->shipping_address_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$shippingAddress) {
                return response()->json([
                    'success' => false,
                    'message' => 'Adresse de livraison invalide.',
                ], 422);
            }

            DB::beginTransaction();

            // ============================================
            // 3. CONSTRUIRE LES ITEMS + CALCULER LES TOTAUX
            // ============================================
            $itemsData = [];
            $subtotal = 0;

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);

                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Produit introuvable (ID: {$item['product_id']}).",
                    ], 422);
                }

                // Vérifier le stock
                if ($product->stock < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour le produit '{$product->name}'.",
                    ], 422);
                }

                // Variante (optionnelle)
                $variant = null;
                if (!empty($item['variant_id'])) {
                    $variant = ProductVariant::find($item['variant_id']);
                }

                // Calcul du prix
                $unitPrice = (float) $product->price;
                $discountPrice = $product->discount_price !== null
                    ? (float) $product->discount_price
                    : null;

                $effectivePrice = $discountPrice ?? $unitPrice;
                $totalPrice = $effectivePrice * $item['quantity'];

                $subtotal += $totalPrice;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'discount_price' => $discountPrice,
                    'total_price' => $totalPrice,
                    'product_name' => $product->name,
                    'product_image' => $product->main_image,
                    'product_sku' => $product->sku,
                    'product_barcode' => $product->barcode,
                    'variant_name' => $variant?->name,
                    'variant_color' => $variant?->color,
                    'variant_size' => $variant?->size,
                    'item_status' => 'pending',
                ];
            }

            // ============================================
            // 4. CALCULS FINAUX
            // ============================================
            $shippingFee = $this->calculateShippingFee(
                $request->shipping_method,
                $subtotal
            );

            $tax = 0;      // À calculer selon le pays
            $discount = 0; // Remise globale éventuelle
            $couponDiscount = 0;

            // Code promo (à implémenter)
            if ($request->filled('coupon_code')) {
                // TODO: valider le coupon et calculer la réduction
                $couponDiscount = 0;
            }

            $total = $subtotal + $shippingFee + $tax - $discount - $couponDiscount;

            // ============================================
            // 5. CRÉER LA COMMANDE
            // ============================================
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user->id,
                'shipping_address_id' => $request->shipping_address_id,
                'billing_address_id' => $request->billing_address_id
                    ?? $request->shipping_address_id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'coupon_discount' => $couponDiscount,
                'shipping_fee' => $shippingFee,
                'tax' => $tax,
                'total' => $total,
                'status' => Order::STATUS_PENDING,
                'payment_status' => 'pending',
                'shipping_method' => $request->shipping_method,
                'coupon_code' => $request->coupon_code,
                'customer_note' => $request->customer_note,
                'currency' => 'FCFA',
            ]);

            // ============================================
            // 6. CRÉER LES ITEMS DE LA COMMANDE
            // ============================================
            foreach ($itemsData as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::create($itemData);
            }

            // ============================================
            // 7. HISTORIQUE
            // ============================================
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'old_status' => null,
                'new_status' => Order::STATUS_PENDING,
                'note' => 'Commande créée',
            ]);

            // ============================================
            // 8. DÉCRÉMENTER LE STOCK
            // ============================================
            foreach ($request->items as $item) {
                Product::where('id', $item['product_id'])
                    ->decrement('stock', $item['quantity']);
            }

            DB::commit();

            // ============================================
            // 9. RECHARGER LA COMMANDE COMPLÈTE
            // ============================================
            $order->load([
                'items.product',
                'items.variant',
                'shippingAddress',
                'billingAddress',
                'payments',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès.',
                'data' => $order,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // ANNULER UNE COMMANDE
    // ============================================
    /**
     * Annuler une commande (uniquement si non expédiée).
     *
     * Endpoint : POST /api/v1/orders/{id}/cancel
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, $id)
    {
        try {
            $user = Auth::user();

            $order = Order::where('user_id', $user->id)->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable.',
                ], 404);
            }

            if (in_array($order->status, [
                Order::STATUS_SHIPPED,
                Order::STATUS_DELIVERED,
                Order::STATUS_CANCELLED,
            ])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande ne peut plus être annulée.',
                ], 422);
            }

            $reason = $request->input('reason', 'Annulée par le client');

            $order->cancel($reason);

            return response()->json([
                'success' => true,
                'message' => 'Commande annulée avec succès.',
                'data' => $order->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de la commande.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // SUIVI D'UNE COMMANDE
    // ============================================
    /**
     * Récupérer le suivi de livraison d'une commande.
     *
     * Endpoint : GET /api/v1/orders/{id}/track
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function track($id)
    {
        try {
            $user = Auth::user();

            $order = Order::where('user_id', $user->id)->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable.',
                ], 404);
            }

            $tracking = $order->trackings()->orderByDesc('created_at')->first();

            return response()->json([
                'success' => true,
                'message' => 'Suivi récupéré avec succès.',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'status_label' => $order->status_label,
                        'tracking_number' => $order->tracking_number,
                        'estimated_delivery_date' => $order->estimated_delivery_date,
                    ],
                    'tracking' => $tracking,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du suivi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // TÉLÉCHARGER LA FACTURE PDF (PAR ID)
    // ============================================
    /**
     * Télécharger la facture PDF d'une commande du client.
     *
     * Endpoint : GET /api/v1/orders/{id}/invoice
     *
     * @param int $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function invoice($id)
    {
        try {
            $user = Auth::user();

            $order = Order::with([
                'user.profile',
                'items.product',
                'items.variant',
                'shippingAddress',
                'billingAddress',
                'payments',
            ])
                ->where('user_id', $user->id)
                ->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable.',
                ], 404);
            }

            return $this->generateInvoicePdf($order);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de la facture.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // TÉLÉCHARGER LA FACTURE PDF (PAR NUMÉRO)
    // ============================================
    /**
     * Télécharger la facture PDF à partir du numéro de commande.
     *
     * Endpoint : GET /api/v1/orders/by-number/{orderNumber}/invoice
     *
     * @param string $orderNumber
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function invoiceByNumber($orderNumber)
    {
        try {
            $user = Auth::user();

            $order = Order::with([
                'user.profile',
                'items.product',
                'items.variant',
                'shippingAddress',
                'billingAddress',
                'payments',
            ])
                ->where('user_id', $user->id)
                ->where('order_number', $orderNumber)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable.',
                ], 404);
            }

            return $this->generateInvoicePdf($order);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de la facture.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // MÉTHODES PRIVÉES
    // ============================================

    /**
     * Calculer les frais de livraison selon la méthode choisie.
     */
    private function calculateShippingFee(?string $method, float $subtotal): float
    {
        switch ($method) {
            case 'express':
                return 5000.0;
            case 'pickup':
                return 1000.0;
            case 'standard':
            default:
                // Livraison gratuite au-delà de 100 000 FCFA
                return $subtotal >= 100000 ? 0.0 : 2000.0;
        }
    }

    /**
     * Générer et retourner le PDF de la facture.
     */
    private function generateInvoicePdf(Order $order)
    {
        // Vérifier que dompdf est disponible
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return response()->json([
                'success' => false,
                'message' => 'Le générateur de PDF n\'est pas installé sur le serveur.',
            ], 500);
        }

        $data = [
            'order' => $order,
            'user' => $order->user,
            'items' => $order->items,
            'shippingAddress' => $order->shippingAddress,
            'billingAddress' => $order->billingAddress,
            'payments' => $order->payments,
            'generated_at' => now(),
            'invoice_number' => 'FAC-' . $order->order_number,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'invoices.order',
            $data
        )->setPaper('A4', 'portrait');

        $filename = 'facture-' . $order->order_number . '.pdf';

        return $pdf->download($filename);
    }
}