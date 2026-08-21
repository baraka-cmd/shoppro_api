<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'product_variants';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'name',
        'color',
        'color_code',
        'size',
        'weight',
        'material',
        'price',
        'discount_price',
        'stock',
        'min_stock',
        'image',
        'is_active',
        'is_default',
        'position',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'float',
        'discount_price' => 'float',
        'stock' => 'integer',
        'min_stock' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'position' => 'integer',
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
     * Relation : La variante appartient à un produit
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relation : Stocks de cette variante
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stocks()
    {
        return $this->hasMany(Stock::class, 'product_variant_id');
    }

    /**
     * Relation : Éléments de panier avec cette variante
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cartItems()
    {
        return $this->hasMany(\App\Models\Order\CartItem::class, 'product_variant_id');
    }

    /**
     * Accesseur : Prix actuel (avec réduction)
     * 
     * @return float
     */
    public function getCurrentPriceAttribute(): float
    {
        return $this->discount_price ?? $this->price ?? $this->product->price;
    }

    /**
     * Accesseur : Pourcentage de réduction
     * 
     * @return int|null
     */
    public function getDiscountPercentageAttribute(): ?int
    {
        $originalPrice = $this->price ?? $this->product->price;
        $currentPrice = $this->getCurrentPriceAttribute();

        if ($originalPrice > 0 && $currentPrice < $originalPrice) {
            return (int) round((($originalPrice - $currentPrice) / $originalPrice) * 100);
        }
        return null;
    }

    /**
     * Accesseur : URL de l'image de la variante
     * 
     * @return string
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return $this->product->main_image_url;
    }

    /**
     * Accesseur : La variante est-elle en stock ?
     * 
     * @return bool
     */
    public function getIsInStockAttribute(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Accesseur : Nom complet avec les attributs
     * 
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        $parts = [$this->product->name];
        
        if ($this->color) {
            $parts[] = $this->color;
        }
        if ($this->size) {
            $parts[] = $this->size;
        }
        
        return implode(' - ', $parts);
    }

    /**
     * Scope : Variantes actives
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Variantes en stock
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Scope : Variantes par couleur
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $color
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByColor($query, string $color)
    {
        return $query->where('color', $color);
    }

    /**
     * Scope : Variantes par taille
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $size
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySize($query, string $size)
    {
        return $query->where('size', $size);
    }

    /**
     * Méthode : Décrémenter le stock de la variante
     * 
     * @param int $quantity
     * @return bool
     */
    public function decrementStock(int $quantity): bool
    {
        if ($this->stock < $quantity) {
            return false;
        }
        
        $this->decrement('stock', $quantity);
        return true;
    }

    /**
     * Méthode : Incrémenter le stock de la variante
     * 
     * @param int $quantity
     * @return void
     */
    public function incrementStock(int $quantity): void
    {
        $this->increment('stock', $quantity);
    }
}