<?php

namespace App\Http\Resources\Api\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource : SellerStatsResource
 *
 * Formate les statistiques vendeur pour l'API.
 *
 * Responsabilités :
 *   - Uniformiser la structure des 3 types de stats (sales, products, customers)
 *   - Fournir les labels et couleurs prêts pour l'UI Flutter
 *   - Convertir les montants en FCFA formaté
 *   - Ajouter les croissances (comparaison période précédente)
 *
 * ⚠️ Sécurité :
 *   - Aucune donnée sensible (email/téléphone clients) n'est exposée
 *   - Les IDs produits/clients sont exposés (utiles pour navigation)
 *   - Les montants sont des agrégats, jamais des données individuelles
 *
 * Structure de la réponse selon le type :
 *   - 'sales'     : KPIs + série temporelle
 *   - 'products'  : Top produits
 *   - 'customers' : Top clients
 *
 * @package App\Http\Resources\Api\Seller
 */
class SellerStatsResource extends JsonResource
{
    /**
     * Type de statistiques à formater.
     * Valeurs : 'sales' | 'products' | 'customers'
     */
    private string $type;

    /**
     * Constructeur custom.
     */
    public function __construct($resource, string $type = 'sales')
    {
        parent::__construct($resource);
        $this->type = $type;
    }

    /**
     * Transforme la ressource en tableau JSON.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return match ($this->type) {
            'sales'     => $this->formatSales($this->resource),
            'products'  => $this->formatProducts($this->resource),
            'customers' => $this->formatCustomers($this->resource),
            default     => [],
        };
    }

    // ============================================
    // SALES (CA + tendance)
    // ============================================

    /**
     * Formate les statistiques de ventes.
     *
     * @param array $data
     * @return array<string, mixed>
     */
    private function formatSales(array $data): array
    {
        $totalRevenue       = (float) ($data['total_revenue'] ?? 0);
        $previousRevenue    = (float) ($data['previous_revenue'] ?? 0);
        $totalOrders        = (int) ($data['total_orders'] ?? 0);
        $previousOrders     = (int) ($data['previous_orders'] ?? 0);
        $totalItems         = (int) ($data['total_items'] ?? 0);
        $previousItems      = (int) ($data['previous_items'] ?? 0);
        $averageOrderValue  = (float) ($data['average_order_value'] ?? 0);
        $previousAvg        = (float) ($data['previous_average_order_value'] ?? 0);

        // Calcul des croissances
        $revenueGrowth  = $this->computeGrowth($totalRevenue, $previousRevenue);
        $ordersGrowth   = $this->computeGrowth($totalOrders, $previousOrders);
        $itemsGrowth    = $this->computeGrowth($totalItems, $previousItems);
        $avgGrowth      = $this->computeGrowth($averageOrderValue, $previousAvg);

        return [
            // ============================================
            // KPIs PRINCIPAUX
            // ============================================
            'kpis' => [
                'total_revenue' => [
                    'value'           => $totalRevenue,
                    'formatted'       => $this->formatPrice($totalRevenue),
                    'growth_percent'  => $revenueGrowth,
                    'growth_label'    => $this->formatGrowthLabel($revenueGrowth),
                    'is_positive'     => $revenueGrowth >= 0,
                    'icon'            => 'payments',
                    'color'           => '#10B981', // success
                ],
                'total_orders' => [
                    'value'           => $totalOrders,
                    'formatted'       => $this->formatNumber($totalOrders),
                    'growth_percent'  => $ordersGrowth,
                    'growth_label'    => $this->formatGrowthLabel($ordersGrowth),
                    'is_positive'     => $ordersGrowth >= 0,
                    'icon'            => 'receipt_long',
                    'color'           => '#3B82F6', // info
                ],
                'total_items' => [
                    'value'           => $totalItems,
                    'formatted'       => $this->formatNumber($totalItems),
                    'growth_percent'  => $itemsGrowth,
                    'growth_label'    => $this->formatGrowthLabel($itemsGrowth),
                    'is_positive'     => $itemsGrowth >= 0,
                    'icon'            => 'inventory_2',
                    'color'           => '#F59E0B', // warning
                ],
                'average_order_value' => [
                    'value'           => $averageOrderValue,
                    'formatted'       => $this->formatPrice($averageOrderValue),
                    'growth_percent'  => $avgGrowth,
                    'growth_label'    => $this->formatGrowthLabel($avgGrowth),
                    'is_positive'     => $avgGrowth >= 0,
                    'icon'            => 'trending_up',
                    'color'           => '#8B5CF6', // violet
                ],
            ],

            // ============================================
            // SÉRIE TEMPORELLE (graphique)
            // ============================================
            'timeline' => $this->formatTimeline($data['timeline'] ?? []),

            // ============================================
            // MÉTADONNÉES
            // ============================================
            'period' => [
                'days'        => (int) ($data['period_days'] ?? 30),
                'label'       => $data['period_label'] ?? '30 derniers jours',
                'start_date'  => $data['start_date'] ?? null,
                'end_date'    => $data['end_date'] ?? null,
            ],

            // ============================================
            // RÉSUMÉ COMPARATIF
            // ============================================
            'comparison' => [
                'current_revenue'   => $totalRevenue,
                'previous_revenue'  => $previousRevenue,
                'current_orders'    => $totalOrders,
                'previous_orders'   => $previousOrders,
                'revenue_change'    => $totalRevenue - $previousRevenue,
                'revenue_change_formatted' => $this->formatPrice($totalRevenue - $previousRevenue),
            ],
        ];
    }

    // ============================================
    // PRODUCTS (top produits)
    // ============================================

    /**
     * Formate les statistiques de produits.
     *
     * @param array $data
     * @return array<string, mixed>
     */
    private function formatProducts(array $data): array
    {
        $products = $data['top_products'] ?? [];
        $categories = $data['top_categories'] ?? [];

        return [
            // ============================================
            // TOP PRODUITS
            // ============================================
            'top_products' => collect($products)->map(function ($product, $index) {
                return [
                    'rank'              => $index + 1,
                    'id'                => (int) ($product['id'] ?? 0),
                    'name'              => $product['name'] ?? 'Produit',
                    'sku'               => $product['sku'] ?? '',
                    'image_url'         => $this->resolveImageUrl($product['main_image'] ?? null),
                    'quantity_sold'     => (int) ($product['quantity_sold'] ?? 0),
                    'quantity_sold_formatted' => $this->formatNumber((int) ($product['quantity_sold'] ?? 0)),
                    'revenue'           => (float) ($product['revenue'] ?? 0),
                    'revenue_formatted' => $this->formatPrice($product['revenue'] ?? 0),
                    'orders_count'      => (int) ($product['orders_count'] ?? 0),
                    'average_rating'    => (float) ($product['average_rating'] ?? 0),
                    'views_count'       => (int) ($product['views_count'] ?? 0),
                    'rank_medal'        => $this->getRankMedal($index + 1),
                ];
            })->toArray(),

            // ============================================
            // TOP CATÉGORIES
            // ============================================
            'top_categories' => collect($categories)->map(function ($category, $index) {
                return [
                    'rank'              => $index + 1,
                    'id'                => (int) ($category['id'] ?? 0),
                    'name'              => $category['name'] ?? 'Catégorie',
                    'products_sold'     => (int) ($category['products_sold'] ?? 0),
                    'revenue'           => (float) ($category['revenue'] ?? 0),
                    'revenue_formatted' => $this->formatPrice($category['revenue'] ?? 0),
                    'percentage'        => (float) ($category['percentage'] ?? 0),
                ];
            })->toArray(),

            // ============================================
            // RÉSUMÉ
            // ============================================
            'summary' => [
                'total_products_sold' => (int) ($data['total_products_sold'] ?? 0),
                'unique_products'     => (int) ($data['unique_products'] ?? 0),
                'total_revenue'       => (float) ($data['total_revenue'] ?? 0),
                'total_revenue_formatted' => $this->formatPrice($data['total_revenue'] ?? 0),
            ],

            'period' => $this->formatPeriod($data),
        ];
    }

    // ============================================
    // CUSTOMERS (top clients)
    // ============================================

    /**
     * Formate les statistiques de clients.
     *
     * @param array $data
     * @return array<string, mixed>
     */
    private function formatCustomers(array $data): array
    {
        $customers = $data['top_customers'] ?? [];

        return [
            // ============================================
            // TOP CLIENTS
            // ============================================
            'top_customers' => collect($customers)->map(function ($customer, $index) {
                $name = $customer['name'] ?? 'Client';
                $initials = $this->computeInitials($name);

                return [
                    'rank'              => $index + 1,
                    'id'                => (int) ($customer['id'] ?? 0),
                    'name'              => $name,
                    'initials'          => $initials,
                    'orders_count'      => (int) ($customer['orders_count'] ?? 0),
                    'total_spent'       => (float) ($customer['total_spent'] ?? 0),
                    'total_spent_formatted' => $this->formatPrice($customer['total_spent'] ?? 0),
                    'average_order'     => (float) ($customer['average_order'] ?? 0),
                    'average_order_formatted' => $this->formatPrice($customer['average_order'] ?? 0),
                    'last_order_at'     => $customer['last_order_at'] ?? null,
                    'last_order_human'  => $customer['last_order_human'] ?? null,
                    'is_loyal'          => (int) ($customer['orders_count'] ?? 0) >= 3,
                    'is_new'            => (int) ($customer['orders_count'] ?? 0) === 1,
                ];
            })->toArray(),

            // ============================================
            // RÉPARTITION NOUVEAUX vs FIDÈLES
            // ============================================
            'segments' => [
                'new_customers'      => (int) ($data['new_customers'] ?? 0),
                'returning_customers'=> (int) ($data['returning_customers'] ?? 0),
                'loyal_customers'    => (int) ($data['loyal_customers'] ?? 0),
                'total_customers'    => (int) ($data['total_customers'] ?? 0),
                'new_percentage'     => (float) ($data['new_percentage'] ?? 0),
                'returning_percentage' => (float) ($data['returning_percentage'] ?? 0),
                'loyal_percentage'   => (float) ($data['loyal_percentage'] ?? 0),
            ],

            // ============================================
            // RÉSUMÉ
            // ============================================
            'summary' => [
                'total_customers'    => (int) ($data['total_customers'] ?? 0),
                'total_revenue'      => (float) ($data['total_revenue'] ?? 0),
                'total_revenue_formatted' => $this->formatPrice($data['total_revenue'] ?? 0),
                'average_customer_value' => (float) ($data['average_customer_value'] ?? 0),
                'average_customer_value_formatted' => $this->formatPrice($data['average_customer_value'] ?? 0),
            ],

            'period' => $this->formatPeriod($data),
        ];
    }

    // ============================================
    // FORMATAGE : TIMELINE
    // ============================================

    /**
     * Formate la série temporelle pour le graphique.
     *
     * @param array $timeline
     * @return array<int, array<string, mixed>>
     */
    private function formatTimeline(array $timeline): array
    {
        return collect($timeline)->map(function ($point) {
            $revenue = (float) ($point['revenue'] ?? 0);
            $orders  = (int) ($point['orders'] ?? 0);

            return [
                'date'              => $point['date'] ?? null,
                'date_formatted'    => $point['date_formatted'] ?? '',
                'day_name'          => $point['day_name'] ?? '',
                'revenue'           => $revenue,
                'revenue_formatted' => $this->formatPrice($revenue),
                'orders'            => $orders,
                'items'             => (int) ($point['items'] ?? 0),
            ];
        })->toArray();
    }

    // ============================================
    // HELPERS : CALCULS
    // ============================================

    /**
     * Calcule le pourcentage de croissance entre 2 valeurs.
     *
     * @param float $current
     * @param float $previous
     * @return float
     */
    private function computeGrowth($current, $previous): float
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Formate le label de croissance (ex: "+12,5%" ou "-5,2%").
     */
    private function formatGrowthLabel($percent): string
    {
        $percent = (float) $percent;

        if ($percent > 0) {
            return '+' . number_format($percent, 1, ',', ' ') . '%';
        }

        if ($percent < 0) {
            return number_format($percent, 1, ',', ' ') . '%';
        }

        return '0%';
    }

    // ============================================
    // HELPERS : FORMATS
    // ============================================

    /**
     * Formate un montant en FCFA.
     */
    private function formatPrice($amount): string
    {
        return number_format((float) $amount, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Formate un nombre (ex: "1,5k" au-delà de 1000).
     */
    private function formatNumber(int $number): string
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1, ',', ' ') . 'M';
        }

        if ($number >= 1000) {
            return number_format($number / 1000, 1, ',', ' ') . 'k';
        }

        return number_format($number, 0, ',', ' ');
    }

    /**
     * Calcule les initiales d'un nom.
     */
    private function computeInitials(string $name): string
    {
        $parts = array_filter(explode(' ', trim($name)));

        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
        }

        if (count($parts) === 1 && !empty($parts[0])) {
            return strtoupper(substr($parts[0], 0, 1));
        }

        return '?';
    }

    /**
     * Retourne une médaille pour le top 3.
     */
    private function getRankMedal(int $rank): ?string
    {
        return match ($rank) {
            1 => '🥇',
            2 => '🥈',
            3 => '🥉',
            default => null,
        };
    }

    /**
     * Résout une URL d'image complète.
     */
    private function resolveImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/' . $path);
    }

    /**
     * Formate la période (bloc commun).
     */
    private function formatPeriod(array $data): array
    {
        return [
            'days'       => (int) ($data['period_days'] ?? 30),
            'label'      => $data['period_label'] ?? '30 derniers jours',
            'start_date' => $data['start_date'] ?? null,
            'end_date'   => $data['end_date'] ?? null,
        ];
    }
}