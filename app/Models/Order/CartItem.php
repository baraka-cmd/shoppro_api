<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductVariant;

class CartItem extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'cart_items';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'discount_price',
        'total_price',
        'product_name',
        'product_image',
        'product_sku',
        'variant_name',
        'variant_color',
        'variant_size',
        'note',
        'is_active',
        'is_saved_for_later',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'float',
        'discount_price' => 'float',
        'total_price' => 'float',
        'is_active' => 'boolean',
        'is_saved_for_later' => 'boolean',
    ];

    /**
     * Relation : L'élément appartient à un panier
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Relation : L'élément concerne un produit
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relation : L'élément concerne une variante (optionnel)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Accesseur : Sous-total formaté
     * 
     * @return string
     */
    public function getSubtotalFormattedAttribute(): string
    {
        return number_format($this->total_price, 2);
    }

    /**
     * Accesseur : URL de l'image du produit
     * 
     * @return string
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->product_image) {
            return asset('storage/' . $this->product_image);
        }
        return asset('images/default-product.png');
    }

    /**
     * Scope : Éléments actifs
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Éléments gardés pour plus tard
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSavedForLater($query)
    {
        return $query->where('is_saved_for_later', true);
    }

    /**
     * Méthode : Mettre à jour la quantité
     * 
     * @param int $quantity
     * @return bool
     */
    public function updateQuantity(int $quantity): bool
    {
        if ($quantity <= 0) {
            $this->delete();
            $this->cart->recalculateTotals();
            return true;
        }

        $this->update([
            'quantity' => $quantity,
            'total_price' => $this->unit_price * $quantity,
        ]);

        $this->cart->recalculateTotals();
        return true;
    }

    /**
     * Méthode : Garder pour plus tard
     * 
     * @return void
     */
    public function saveForLater(): void
    {
        $this->update(['is_saved_for_later' => true]);
        $this->cart->recalculateTotals();
    }

    /**
     * Méthode : Remettre dans le panier
     * 
     * @return void
     */
    public function moveToCart(): void
    {
        $this->update(['is_saved_for_later' => false]);
        $this->cart->recalculateTotals();
    }
}