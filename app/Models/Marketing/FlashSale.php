<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Catalog\Product;

class FlashSale extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'flash_sales';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'discount_percentage',
        'maximum_discount',
        'start_time',
        'end_time',
        'max_items_per_user',
        'total_stock_limit',
        'sold_count',
        'banner',
        'is_active',
        'is_featured',
        'show_countdown',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'discount_percentage' => 'float',
        'maximum_discount' => 'float',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'max_items_per_user' => 'integer',
        'total_stock_limit' => 'integer',
        'sold_count' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'show_countdown' => 'boolean',
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
        'start_time',
        'end_time',
    ];

    /**
     * Relation : Produits en vente flash
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    // Remplace la relation products() par :
    public function products()
    {
        return $this->belongsToMany(Product::class, 'flash_sale_product')
                    ->withTimestamps()
                    ->withPivot('sold_count', 'stock_limit');
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
        return asset('images/default-flash-sale.png');
    }

    /**
     * Accesseur : La vente flash est-elle en cours ?
     * 
     * @return bool
     */
    public function getIsActiveNowAttribute(): bool
    {
        return now()->between($this->start_time, $this->end_time);
    }

    /**
     * Accesseur : La vente flash est-elle terminée ?
     * 
     * @return bool
     */
    public function getIsEndedAttribute(): bool
    {
        return now()->gt($this->end_time);
    }

    /**
     * Accesseur : La vente flash va-t-elle bientôt commencer ?
     * 
     * @return bool
     */
    public function getIsUpcomingAttribute(): bool
    {
        return now()->lt($this->start_time);
    }

    /**
     * Accesseur : Temps restant en secondes
     * 
     * @return int
     */
    public function getRemainingSecondsAttribute(): int
    {
        if ($this->is_ended) {
            return 0;
        }
        
        return max(0, now()->diffInSeconds($this->end_time));
    }

    /**
     * Accesseur : Progression de la vente
     * 
     * @return float
     */
    public function getProgressPercentageAttribute(): float
    {
        if (!$this->total_stock_limit || $this->total_stock_limit <= 0) {
            return 0;
        }
        
        return round(($this->sold_count / $this->total_stock_limit) * 100, 1);
    }

    /**
     * Scope : Ventes flash actives
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Ventes flash en cours
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_active', true)
                     ->where('start_time', '<=', now())
                     ->where('end_time', '>=', now());
    }

    /**
     * Scope : Ventes flash à venir
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpcoming($query)
    {
        return $query->where('is_active', true)
                     ->where('start_time', '>', now());
    }
}