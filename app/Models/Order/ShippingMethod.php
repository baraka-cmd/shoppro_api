<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingMethod extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'shipping_methods';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'base_cost',
        'cost_per_kg',
        'free_shipping_threshold',
        'min_delivery_days',
        'max_delivery_days',
        'countries',
        'cities',
        'excluded_areas',
        'max_weight',
        'max_dimensions',
        'icon',
        'position',
        'is_active',
        'is_default',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'base_cost' => 'float',
        'cost_per_kg' => 'float',
        'free_shipping_threshold' => 'float',
        'min_delivery_days' => 'integer',
        'max_delivery_days' => 'integer',
        'max_weight' => 'float',
        'max_dimensions' => 'float',
        'position' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'countries' => 'array',
        'cities' => 'array',
        'excluded_areas' => 'array',
    ];

    /**
     * Dates à traiter comme des dates Carbon
     * 
     * @var array<string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Relation : Commandes utilisant cette méthode
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'shipping_method', 'code');
    }

    /**
     * Accesseur : Délai de livraison formaté
     * 
     * @return string
     */
    public function getDeliveryTimeLabelAttribute(): string
    {
        if ($this->min_delivery_days === $this->max_delivery_days) {
            return $this->min_delivery_days . ' jour(s)';
        }
        
        return $this->min_delivery_days . ' à ' . $this->max_delivery_days . ' jours';
    }

    /**
     * Accesseur : Coût formaté
     * 
     * @return string
     */
    public function getCostLabelAttribute(): string
    {
        if ($this->base_cost <= 0) {
            return 'Gratuit';
        }
        
        return number_format($this->base_cost, 2) . ' FCFA';
    }

    /**
     * Méthode : Calculer les frais de livraison
     * 
     * @param float $orderTotal
     * @param float $weight
     * @return float
     */
    public function calculateCost(float $orderTotal, float $weight = 0): float
    {
        // Livraison gratuite si le seuil est atteint
        if ($this->free_shipping_threshold && $orderTotal >= $this->free_shipping_threshold) {
            return 0;
        }

        // Coût de base + coût par kg
        $cost = $this->base_cost;
        
        if ($this->cost_per_kg > 0 && $weight > 0) {
            $cost += $this->cost_per_kg * $weight;
        }

        return $cost;
    }

    /**
     * Méthode : Vérifier si la méthode est disponible pour un pays
     * 
     * @param string $country
     * @return bool
     */
    public function isAvailableForCountry(string $country): bool
    {
        if (empty($this->countries)) {
            return true;
        }
        
        return in_array($country, $this->countries);
    }

    /**
     * Scope : Méthodes actives
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Méthodes triées par position
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc');
    }
}