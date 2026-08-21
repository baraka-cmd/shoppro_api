<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Cart extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'carts';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'subtotal',
        'discount',
        'tax',
        'shipping_fee',
        'total',
        'coupon_code',
        'coupon_discount',
        'items_count',
        'total_items',
        'currency',
        'last_activity_at',
        'abandoned_at',
        'is_active',
        'is_abandoned',
        'is_converted',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'float',
        'discount' => 'float',
        'tax' => 'float',
        'shipping_fee' => 'float',
        'total' => 'float',
        'coupon_discount' => 'float',
        'items_count' => 'integer',
        'total_items' => 'integer',
        'last_activity_at' => 'datetime',
        'abandoned_at' => 'datetime',
        'is_active' => 'boolean',
        'is_abandoned' => 'boolean',
        'is_converted' => 'boolean',
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
        'last_activity_at',
        'abandoned_at',
    ];

    /**
     * Relation : Le panier appartient à un utilisateur (optionnel)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Éléments du panier
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Accesseur : Le panier est-il vide ?
     * 
     * @return bool
     */
    public function getIsEmptyAttribute(): bool
    {
        return $this->items_count <= 0;
    }

    /**
     * Accesseur : Total formaté
     * 
     * @return string
     */
    public function getTotalFormattedAttribute(): string
    {
        return number_format($this->total, 2) . ' ' . $this->currency;
    }

    /**
     * Scope : Paniers actifs
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Paniers abandonnés
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAbandoned($query)
    {
        return $query->where('is_abandoned', true);
    }

    /**
     * Scope : Paniers par utilisateur
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Méthode : Recalculer les totaux du panier
     * 
     * @return void
     */
    public function recalculateTotals(): void
    {
        $items = $this->items()->where('is_active', true)->get();
        
        $subtotal = $items->sum('total_price');
        $totalItems = $items->sum('quantity');
        $itemsCount = $items->count();
        
        $discount = $this->coupon_discount ?? 0;
        $tax = $subtotal * 0.1925; // TVA 19.25% (exemple)
        $shippingFee = $this->shipping_fee ?? 0;
        $total = $subtotal - $discount + $tax + $shippingFee;
        
        $this->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => max(0, $total),
            'items_count' => $itemsCount,
            'total_items' => $totalItems,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Méthode : Ajouter un produit au panier
     * 
     * @param int $productId
     * @param int $quantity
     * @param int|null $variantId
     * @return CartItem
     */
    public function addItem(int $productId, int $quantity = 1, ?int $variantId = null): CartItem
    {
        $product = \App\Models\Catalog\Product::findOrFail($productId);
        
        $item = $this->items()
                    ->where('product_id', $productId)
                    ->where('product_variant_id', $variantId)
                    ->first();

        if ($item) {
            $item->update([
                'quantity' => $item->quantity + $quantity,
                'total_price' => ($item->quantity + $quantity) * $item->unit_price,
            ]);
        } else {
            $price = $variantId 
                ? $product->variants()->find($variantId)?->current_price 
                : $product->current_price;
            
            $item = $this->items()->create([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_price' => $price,
                'total_price' => $price * $quantity,
                'product_name' => $product->name,
                'product_image' => $product->main_image,
                'product_sku' => $product->sku,
            ]);
        }

        $this->recalculateTotals();
        return $item;
    }

    /**
     * Méthode : Vider le panier
     * 
     * @return void
     */
    public function clearCart(): void
    {
        $this->items()->delete();
        $this->update([
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 0,
            'items_count' => 0,
            'total_items' => 0,
            'coupon_code' => null,
            'coupon_discount' => 0,
        ]);
    }

    /**
     * Méthode : Convertir le panier en commande
     * 
     * @return Order
     */
    public function convertToOrder(): Order
    {
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $this->user_id,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'coupon_discount' => $this->coupon_discount,
            'shipping_fee' => $this->shipping_fee,
            'tax' => $this->tax,
            'total' => $this->total,
            'coupon_code' => $this->coupon_code,
            'currency' => $this->currency,
        ]);

        foreach ($this->items as $cartItem) {
            $order->items()->create([
                'product_id' => $cartItem->product_id,
                'product_variant_id' => $cartItem->product_variant_id,
                'quantity' => $cartItem->quantity,
                'unit_price' => $cartItem->unit_price,
                'total_price' => $cartItem->total_price,
                'product_name' => $cartItem->product_name,
                'product_image' => $cartItem->product_image,
                'product_sku' => $cartItem->product_sku,
            ]);
        }

        $this->update([
            'is_active' => false,
            'is_converted' => true,
        ]);

        $this->clearCart();

        return $order;
    }
}