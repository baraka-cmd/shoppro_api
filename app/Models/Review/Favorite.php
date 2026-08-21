<?php

namespace App\Models\Review;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Catalog\Product;

class Favorite extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'favorites';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'note',
        'collection_name',
        'price_alert',
        'price_alert_threshold',
        'added_at',
        'is_active',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'price_alert' => 'boolean',
        'price_alert_threshold' => 'float',
        'added_at' => 'datetime',
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
        'added_at',
    ];

    /**
     * Relation : Le favori appartient à un utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Le favori concerne un produit
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accesseur : Le prix a-t-il baissé ?
     * 
     * @return bool
     */
    public function getHasPriceDroppedAttribute(): bool
    {
        if (!$this->price_alert_threshold) {
            return false;
        }
        
        $currentPrice = $this->product->current_price;
        return $currentPrice <= $this->price_alert_threshold;
    }

    /**
     * Accesseur : Date d'ajout formatée
     * 
     * @return string
     */
    public function getAddedDateFormattedAttribute(): string
    {
        return $this->added_at->format('d/m/Y');
    }

    /**
     * Scope : Favoris actifs
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Favoris par collection
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $collectionName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCollection($query, string $collectionName)
    {
        return $query->where('collection_name', $collectionName);
    }

    /**
     * Scope : Favoris avec alerte de prix
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithPriceAlert($query)
    {
        return $query->where('price_alert', true);
    }

    /**
     * Méthode : Activer/désactiver le favori
     * 
     * @return void
     */
    public function toggle(): void
    {
        $this->update([
            'is_active' => !$this->is_active,
        ]);
    }

    /**
     * Méthode : Vérifier si le prix a atteint le seuil
     * 
     * @return bool
     */
    public function checkPriceAlert(): bool
    {
        if (!$this->price_alert || !$this->price_alert_threshold) {
            return false;
        }
        
        return $this->product->current_price <= $this->price_alert_threshold;
    }
}