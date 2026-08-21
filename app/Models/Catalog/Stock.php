<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'stocks';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'product_variant_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'min_quantity',
        'max_quantity',
        'location',
        'shelf',
        'bin',
        'unit_cost',
        'total_value',
        'last_counted_at',
        'expiry_date',
        'is_active',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'available_quantity' => 'integer',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'unit_cost' => 'float',
        'total_value' => 'float',
        'last_counted_at' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
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
        'last_counted_at',
        'expiry_date',
    ];

    /**
     * Relation : Le stock appartient à un produit
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relation : Le stock appartient à un entrepôt
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Relation : Le stock concerne une variante (optionnel)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Accesseur : Le stock est-il faible ?
     * 
     * @return bool
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->available_quantity <= $this->min_quantity;
    }

    /**
     * Accesseur : Le stock est-il épuisé ?
     * 
     * @return bool
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->available_quantity <= 0;
    }

    /**
     * Accesseur : Le stock est-il proche de l'expiration ?
     * 
     * @return bool
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return now()->diffInDays($this->expiry_date, false) <= 30;
    }

    /**
     * Accesseur : Emplacement complet
     * 
     * @return string
     */
    public function getFullLocationAttribute(): string
    {
        $parts = [
            $this->location,
            $this->shelf ? "Étagère: {$this->shelf}" : null,
            $this->bin ? "Bac: {$this->bin}" : null,
        ];

        return implode(' - ', array_filter($parts)) ?: 'Non défini';
    }

    /**
     * Scope : Stocks actifs
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Stocks faibles
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('available_quantity', '<=', 'min_quantity');
    }

    /**
     * Scope : Stocks épuisés
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('available_quantity', '<=', 0);
    }

    /**
     * Méthode : Réserver du stock
     * 
     * @param int $quantity
     * @return bool
     */
    public function reserve(int $quantity): bool
    {
        if ($this->available_quantity < $quantity) {
            return false;
        }

        $this->update([
            'reserved_quantity' => $this->reserved_quantity + $quantity,
            'available_quantity' => $this->available_quantity - $quantity,
        ]);

        return true;
    }

    /**
     * Méthode : Libérer du stock réservé
     * 
     * @param int $quantity
     * @return bool
     */
    public function release(int $quantity): bool
    {
        if ($this->reserved_quantity < $quantity) {
            return false;
        }

        $this->update([
            'reserved_quantity' => $this->reserved_quantity - $quantity,
            'available_quantity' => $this->available_quantity + $quantity,
        ]);

        return true;
    }

    /**
     * Méthode : Ajuster le stock
     * 
     * @param int $newQuantity
     * @param string $reason
     * @return void
     */
    public function adjust(int $newQuantity, string $reason = 'manual_adjustment'): void
    {
        $oldQuantity = $this->quantity;
        $difference = $newQuantity - $oldQuantity;

        $this->update([
            'quantity' => $newQuantity,
            'available_quantity' => $this->available_quantity + $difference,
            'last_counted_at' => now(),
        ]);

        // Créer un mouvement de stock
        StockMovement::create([
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'warehouse_id' => $this->warehouse_id,
            'type' => 'adjustment',
            'quantity' => $difference,
            'quantity_before' => $oldQuantity,
            'quantity_after' => $newQuantity,
            'note' => $reason,
        ]);
    }
}