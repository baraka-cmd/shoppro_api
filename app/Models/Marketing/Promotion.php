<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Catalog\Product;

class Promotion extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'promotions';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'discount_value',
        'discount_type',
        'start_date',
        'end_date',
        'banner',
        'banner_mobile',
        'minimum_order',
        'usage_limit',
        'usage_count',
        'is_active',
        'is_featured',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'discount_value' => 'float',
        'minimum_order' => 'float',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
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
     * Relation : Produits en promotion
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    // Remplace la relation products() par :
    public function products()
    {
        return $this->belongsToMany(Product::class, 'promotion_product')
                    ->withTimestamps();
    }

    /**
     * Accesseur : Type en français
     * 
     * @return string
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'discount' => 'Réduction',
            'bogo' => 'Achetez-en un, obtenez-en un',
            'bundle' => 'Offre groupée',
            'flash_sale' => 'Vente flash',
            'clearance' => 'Liquidation',
            default => $this->type,
        };
    }

    /**
     * Accesseur : Type de réduction en français
     * 
     * @return string
     */
    public function getDiscountTypeLabelAttribute(): string
    {
        return $this->discount_type === 'percentage' ? 'Pourcentage' : 'Montant fixe';
    }

    /**
     * Accesseur : Valeur formatée
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
     * Accesseur : URL de la bannière
     * 
     * @return string
     */
    public function getBannerUrlAttribute(): string
    {
        if ($this->banner) {
            return asset('storage/' . $this->banner);
        }
        return asset('images/default-banner.png');
    }

    /**
     * Accesseur : La promotion est-elle valide ?
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
     * Scope : Promotions actives
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Promotions valides
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

    /**
     * Scope : Promotions mises en avant
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}