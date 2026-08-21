<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Catalog\Product;

class Supplier extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'suppliers';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'contact_name',
        'email',
        'phone',
        'phone_alt',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'country',
        'bank_name',
        'account_number',
        'account_name',
        'products_count',
        'total_purchases',
        'orders_count',
        'last_order_date',
        'payment_terms',
        'delivery_time',
        'minimum_order',
        'is_active',
        'is_preferred',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'products_count' => 'integer',
        'total_purchases' => 'float',
        'orders_count' => 'integer',
        'last_order_date' => 'date',
        'payment_terms' => 'integer',
        'delivery_time' => 'integer',
        'minimum_order' => 'float',
        'is_active' => 'boolean',
        'is_preferred' => 'boolean',
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
        'last_order_date',
    ];

    /**
     * Relation : Produits fournis par ce fournisseur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'supplier_id');
    }

    /**
     * Accesseur : Adresse complète
     * 
     * @return string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->country,
        ];

        return implode(', ', array_filter($parts));
    }

    /**
     * Accesseur : Délai de paiement formaté
     * 
     * @return string
     */
    public function getPaymentTermsLabelAttribute(): string
    {
        return $this->payment_terms . ' jours';
    }

    /**
     * Accesseur : Délai de livraison formaté
     * 
     * @return string
     */
    public function getDeliveryTimeLabelAttribute(): string
    {
        return $this->delivery_time . ' jours';
    }

    /**
     * Scope : Fournisseurs actifs
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Fournisseurs préférés
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePreferred($query)
    {
        return $query->where('is_preferred', true);
    }

    /**
     * Méthode : Mettre à jour les statistiques
     * 
     * @return void
     */
    public function updateStats(): void
    {
        $this->update([
            'products_count' => $this->products()->count(),
        ]);
    }

    /**
     * Méthode : Enregistrer une commande fournisseur
     * 
     * @param float $amount
     * @return void
     */
    public function recordPurchase(float $amount): void
    {
        $this->update([
            'total_purchases' => $this->total_purchases + $amount,
            'orders_count' => $this->orders_count + 1,
            'last_order_date' => now(),
        ]);
    }
}