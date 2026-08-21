<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'coupons';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'maximum_discount',
        'minimum_order',
        'usage_limit',
        'usage_per_user',
        'usage_count',
        'start_date',
        'end_date',
        'categories',
        'products',
        'users',
        'is_active',
        'is_public',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'discount_value' => 'float',
        'maximum_discount' => 'float',
        'minimum_order' => 'float',
        'usage_limit' => 'integer',
        'usage_per_user' => 'integer',
        'usage_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'categories' => 'array',
        'products' => 'array',
        'users' => 'array',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
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
        'start_date',
        'end_date',
    ];

    /**
     * Relation : Commandes utilisant ce coupon
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(\App\Models\Order\Order::class, 'coupon_code', 'code');
    }

    /**
     * Accesseur : Type de réduction en français
     * 
     * @return string
     */
    public function getDiscountTypeLabelAttribute(): string
    {
        return match($this->discount_type) {
            'percentage' => 'Pourcentage',
            'fixed' => 'Montant fixe',
            'free_shipping' => 'Livraison gratuite',
            default => $this->discount_type,
        };
    }

    /**
     * Accesseur : Valeur de réduction formatée
     * 
     * @return string
     */
    public function getDiscountValueFormattedAttribute(): string
    {
        if ($this->discount_type === 'percentage') {
            return $this->discount_value . '%';
        }
        
        return number_format($this->discount_value, 2) . ' FCFA';
    }

    /**
     * Accesseur : Le coupon est-il expiré ?
     * 
     * @return bool
     */
    public function getIsExpiredAttribute(): bool
    {
        return now()->gt($this->end_date);
    }

    /**
     * Accesseur : Le coupon est-il valide ?
     * 
     * @return bool
     */
    public function getIsValidAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        if (now()->lt($this->start_date) || now()->gt($this->end_date)) {
            return false;
        }
        
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }
        
        return true;
    }

    /**
     * Méthode : Calculer la réduction
     * 
     * @param float $orderTotal
     * @return float
     */
    public function calculateDiscount(float $orderTotal): float
    {
        if (!$this->is_valid) {
            return 0;
        }
        
        if ($orderTotal < $this->minimum_order) {
            return 0;
        }
        
        $discount = 0;
        
        if ($this->discount_type === 'percentage') {
            $discount = ($orderTotal * $this->discount_value / 100);
            
            if ($this->maximum_discount && $discount > $this->maximum_discount) {
                $discount = $this->maximum_discount;
            }
        } elseif ($this->discount_type === 'fixed') {
            $discount = $this->discount_value;
        }
        
        return min($discount, $orderTotal);
    }

    /**
     * Méthode : Incrémenter le compteur d'utilisation
     * 
     * @return void
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Scope : Coupons actifs
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Coupons valides (date et usage)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->where('is_active', true)
                     ->where('start_date', '<=', now())
                     ->where('end_date', '>=', now());
    }
}