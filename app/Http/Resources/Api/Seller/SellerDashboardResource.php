<?php

namespace App\Http\Resources\Api\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->resource;

        return [
            'seller' => $this->formatSeller($data['seller'] ?? null),
            'kpis' => [
                'revenue'  => $this->formatRevenue($data['kpis']['revenue'] ?? []),
                'orders'   => $this->formatOrders($data['kpis']['orders'] ?? []),
                'products' => $this->formatProducts($data['kpis']['products'] ?? []),
                'reviews'  => $this->formatReviews($data['kpis']['reviews'] ?? []),
                'balance'  => $this->formatBalance($data['kpis']['balance'] ?? []),
            ],
            'alerts'        => $this->formatAlerts($data['alerts'] ?? []),
            'alerts_count'  => count($data['alerts'] ?? []),
            'recent_orders' => $this->formatRecentOrders($data['recent_orders'] ?? []),
            'top_products'  => $this->formatTopProducts($data['top_products'] ?? []),
            'generated_at'  => now()->toIso8601String(),
            'from_cache'    => $data['from_cache'] ?? false,
        ];
    }

    private function formatSeller($seller): ?array
    {
        if (!$seller) return null;
        return [
            'id'               => is_array($seller) ? ($seller['id'] ?? 0) : $seller->id,
            'user_id'          => is_array($seller) ? ($seller['user_id'] ?? 0) : $seller->user_id,
            'shop_name'        => is_array($seller) ? ($seller['shop_name'] ?? '') : $seller->shop_name,
            'shop_slug'        => is_array($seller) ? ($seller['shop_slug'] ?? '') : $seller->shop_slug,
            'logo_url'         => is_array($seller) ? ($seller['logo_url'] ?? null) : $seller->logo_url,
            'kyc_status'       => is_array($seller) ? ($seller['kyc_status'] ?? 'pending') : $seller->kyc_status,
            'kyc_status_label' => is_array($seller) ? ($seller['kyc_status_label'] ?? 'En attente') : $seller->kyc_status_label,
            'is_verified'      => (bool) (is_array($seller) ? ($seller['is_verified'] ?? false) : $seller->is_verified),
            'is_active'        => (bool) (is_array($seller) ? ($seller['is_active'] ?? false) : $seller->is_active),
        ];
    }

    private function formatRevenue(array $data): array
    {
        $total           = (float) ($data['total'] ?? 0);
        $thisMonth       = (float) ($data['this_month'] ?? 0);
        $lastMonth       = (float) ($data['last_month'] ?? 0);
        $totalCommission = (float) ($data['total_commission'] ?? 0);

        return [
            'total'              => $total,
            'total_formatted'    => $this->formatPrice($total),
            'this_month'         => $thisMonth,
            'this_month_formatted' => $this->formatPrice($thisMonth),
            'last_month'         => $lastMonth,
            'last_month_formatted' => $this->formatPrice($lastMonth),
            'growth_percent'     => (float) ($data['growth_percent'] ?? 0),
            'growth_label'       => $this->formatGrowth($data['growth_percent'] ?? 0),
            'total_commission'   => $totalCommission,
            'total_commission_formatted' => $this->formatPrice($totalCommission),
            'net_revenue'        => $total - $totalCommission,
            'net_revenue_formatted' => $this->formatPrice($total - $totalCommission),
        ];
    }

    private function formatOrders(array $data): array
    {
        return [
            'total'          => (int) ($data['total'] ?? 0),
            'this_month'     => (int) ($data['this_month'] ?? 0),
            'last_month'     => (int) ($data['last_month'] ?? 0),
            'to_accept'      => (int) ($data['to_accept'] ?? 0),
            'to_prepare'     => (int) ($data['to_prepare'] ?? 0),
            'to_ship'        => (int) ($data['to_ship'] ?? 0),
            'shipped'        => (int) ($data['shipped'] ?? 0),
            'delivered'      => (int) ($data['delivered'] ?? 0),
            'cancelled'      => (int) ($data['cancelled'] ?? 0),
            'pending_count'  => (int) ($data['to_accept'] ?? 0) + (int) ($data['to_prepare'] ?? 0),
        ];
    }

    private function formatProducts(array $data): array
    {
        return [
            'total'         => (int) ($data['total'] ?? 0),
            'active'        => (int) ($data['active'] ?? 0),
            'drafts'        => (int) ($data['drafts'] ?? 0),
            'out_of_stock'  => (int) ($data['out_of_stock'] ?? 0),
            'low_stock'     => (int) ($data['low_stock'] ?? 0),
            'archived'      => (int) ($data['archived'] ?? 0),
            'total_views'   => (int) ($data['total_views'] ?? 0),
            'total_sales'   => (int) ($data['total_sales'] ?? 0),
        ];
    }

    private function formatReviews(array $data): array
    {
        $avg = (float) ($data['average_rating'] ?? 0);
        return [
            'average_rating'           => round($avg, 2),
            'average_rating_formatted' => number_format($avg, 1) . '/5',
            'total'                    => (int) ($data['total'] ?? 0),
            'pending'                  => (int) ($data['pending'] ?? 0),
            'approved'                 => (int) ($data['approved'] ?? 0),
            'rejected'                 => (int) ($data['rejected'] ?? 0),
            'with_photos'              => (int) ($data['with_photos'] ?? 0),
            'to_moderate'              => (int) ($data['pending'] ?? 0),
        ];
    }

    private function formatBalance(array $data): array
    {
        $available = (float) ($data['available'] ?? 0);
        $pending   = (float) ($data['pending'] ?? 0);
        return [
            'available'                    => $available,
            'available_formatted'          => $this->formatPrice($available),
            'pending'                      => $pending,
            'pending_formatted'            => $this->formatPrice($pending),
            'total'                        => $available + $pending,
            'total_formatted'              => $this->formatPrice($available + $pending),
            'can_request_payout'           => (bool) ($data['can_request_payout'] ?? false),
            'payout_method'                => $data['payout_method'] ?? null,
            'has_payout_method'            => !empty($data['payout_method']),
        ];
    }

    private function formatAlerts(array $alerts): array
    {
        return array_map(function ($alert) {
            return [
                'type'    => $alert['type'] ?? 'info',
                'level'   => $alert['level'] ?? 'info',
                'message' => $alert['message'] ?? '',
                'count'   => (int) ($alert['count'] ?? 0),
                'action'  => $alert['action'] ?? null,
                'icon'    => $this->getAlertIcon($alert['type'] ?? 'info'),
            ];
        }, $alerts);
    }

    private function getAlertIcon(string $type): string
    {
        return match ($type) {
            'stock_low'         => 'inventory_2',
            'stock_out'         => 'error',
            'orders_to_accept'  => 'pending_actions',
            'orders_to_ship'    => 'local_shipping',
            'reviews_pending'   => 'rate_review',
            'kyc_pending'       => 'verified_user',
            default             => 'info',
        };
    }

    private function formatRecentOrders($orders): array
    {
        if (!is_iterable($orders)) return [];
        $result = [];

        foreach ($orders as $order) {
            // Le contrôleur du dashboard fournit déjà des résumés prêts à
            // l'emploi (sans la collection brute des articles). Ne pas les
            // re-filtrer comme des modèles Order : cela supprimait la liste.
            if (is_array($order) && array_key_exists('items_count', $order)) {
                $total = (float) ($order['total'] ?? 0);
                $result[] = [
                    'id'               => (int) ($order['id'] ?? 0),
                    'order_number'     => (string) ($order['order_number'] ?? ''),
                    'status'           => (string) ($order['status'] ?? 'pending'),
                    'status_label'     => (string) ($order['status_label'] ?? 'En attente'),
                    'payment_status'   => (string) ($order['payment_status'] ?? 'pending'),
                    'customer_name'    => (string) ($order['customer_name'] ?? 'Client'),
                    'items_count'      => (int) $order['items_count'],
                    'total'            => $total,
                    'total_formatted'  => $order['total_formatted'] ?? $this->formatPrice($total),
                    'created_at'       => $order['created_at'] ?? null,
                    'created_at_human' => $order['created_at_human'] ?? null,
                ];
                continue;
            }

            $items = is_array($order) ? ($order['items'] ?? []) : $order->items;
            $sellerItems = [];
            $sellerTotal = 0;

            foreach ($items as $item) {
                $itemSellerId = is_array($item) ? ($item['seller_id'] ?? null) : $item->seller_id;
                $itemPrice = is_array($item) ? ($item['total_price'] ?? 0) : $item->total_price;

                if ($itemSellerId !== null) {
                    $sellerItems[] = $item;
                    $sellerTotal  += (float) $itemPrice;
                }
            }

            if (empty($sellerItems)) continue;

            $createdAt = is_array($order) ? ($order['created_at'] ?? null) : $order->created_at;
            $createdAtHuman = is_array($order) ? ($order['created_at_human'] ?? null) : $order->created_at?->diffForHumans();

            $result[] = [
                'id'               => is_array($order) ? ($order['id'] ?? 0) : $order->id,
                'order_number'     => is_array($order) ? ($order['order_number'] ?? '') : $order->order_number,
                'status'           => is_array($order) ? ($order['status'] ?? 'pending') : $order->status,
                'status_label'     => is_array($order) ? ($order['status_label'] ?? 'En attente') : $order->status_label,
                'payment_status'   => is_array($order) ? ($order['payment_status'] ?? 'pending') : $order->payment_status,
                'customer_name'    => is_array($order) ? ($order['customer_name'] ?? 'Client') : ($order->user?->full_name ?? 'Client'),
                'items_count'      => count($sellerItems),
                'total'            => $sellerTotal,
                'total_formatted'  => $this->formatPrice($sellerTotal),
                'created_at'       => $createdAt,
                'created_at_human' => $createdAtHuman,
            ];
        }

        return $result;
    }

    private function formatTopProducts($products): array
    {
        if (!is_iterable($products)) return [];
        $result = [];

        foreach ($products as $product) {
            $result[] = [
                'id'             => is_array($product) ? ($product['id'] ?? 0) : $product->id,
                'name'           => is_array($product) ? ($product['name'] ?? '') : $product->name,
                'slug'           => is_array($product) ? ($product['slug'] ?? '') : $product->slug,
                'main_image_url' => is_array($product) ? ($product['main_image_url'] ?? null) : $product->main_image_url,
                'price'          => (float) (is_array($product) ? ($product['price'] ?? 0) : $product->price),
                'price_formatted'=> $this->formatPrice(is_array($product) ? ($product['price'] ?? 0) : $product->price),
                'sales_count'    => (int) (is_array($product) ? ($product['sales_count'] ?? 0) : $product->sales_count),
                'views_count'    => (int) (is_array($product) ? ($product['views_count'] ?? 0) : $product->views_count),
                'average_rating' => (float) (is_array($product) ? ($product['average_rating'] ?? 0) : $product->average_rating),
                'stock'          => (int) (is_array($product) ? ($product['stock'] ?? 0) : $product->stock),
                'is_active'      => (bool) (is_array($product) ? ($product['is_active'] ?? true) : $product->is_active),
            ];
        }

        return $result;
    }

    private function formatPrice($amount): string
    {
        return number_format((float) $amount, 0, ',', ' ') . ' FCFA';
    }

    private function formatGrowth($percent): string
    {
        $percent = (float) $percent;
        if ($percent > 0) return '+' . number_format($percent, 1) . '%';
        return number_format($percent, 1) . '%';
    }
}
