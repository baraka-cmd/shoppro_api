<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductVariant;

class OrderItem extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'order_items';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'discount_price',
        'total_price',
        'product_name',
        'product_image',
        'product_sku',
        'product_barcode',
        'variant_name',
        'variant_color',
        'variant_size',
        'item_status',
        'is_returned',
        'return_date',
        'return_reason',
        'is_refunded',
        'refund_amount',
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
        'return_date' => 'date',
        'is_returned' => 'boolean',
        'is_refunded' => 'boolean',
        'refund_amount' => 'float',
    ];

    /**
     * Dates à traiter comme des dates Carbon
     * 
     * @var array<string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'return_date',
    ];

    /**
     * Relation : L'élément appartient à une commande
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
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
     * Accesseur : Statut en français
     * 
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->item_status) {
            'pending' => 'En attente',
            'shipped' => 'Expédié',
            'delivered' => 'Livré',
            'cancelled' => 'Annulé',
            'returned' => 'Retourné',
            default => $this->item_status,
        };
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
     * Accesseur : URL de l'image
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
     * Méthode : Marquer comme retourné
     * 
     * @param string $reason
     * @return void
     */
    public function markAsReturned(string $reason): void
    {
        $this->update([
            'item_status' => 'returned',
            'is_returned' => true,
            'return_date' => now(),
            'return_reason' => $reason,
        ]);
    }

    /**
     * Méthode : Marquer comme remboursé
     * 
     * @param float $amount
     * @return void
     */
    public function markAsRefunded(float $amount): void
    {
        $this->update([
            'is_refunded' => true,
            'refund_amount' => $amount,
        ]);
    }
}