<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'warehouses';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'phone',
        'email',
        'manager_name',
        'capacity',
        'current_occupancy',
        'unit',
        'is_active',
        'is_default',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'capacity' => 'integer',
        'current_occupancy' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
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
     * Relation : Stocks dans cet entrepôt
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Relation : Mouvements de stock de cet entrepôt
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Accesseur : Adresse complète formatée
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
            $this->postal_code,
            $this->country,
        ];

        return implode(', ', array_filter($parts));
    }

    /**
     * Accesseur : Taux d'occupation
     * 
     * @return float
     */
    public function getOccupancyRateAttribute(): float
    {
        if (!$this->capacity || $this->capacity <= 0) {
            return 0;
        }
        return round(($this->current_occupancy / $this->capacity) * 100, 2);
    }

    /**
     * Accesseur : L'entrepôt est-il plein ?
     * 
     * @return bool
     */
    public function getIsFullAttribute(): bool
    {
        if (!$this->capacity || $this->capacity <= 0) {
            return false;
        }
        return $this->current_occupancy >= $this->capacity;
    }

    /**
     * Scope : Entrepôts actifs
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Entrepôts par ville
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $city
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Méthode : Mettre à jour l'occupation
     * 
     * @return void
     */
    public function updateOccupancy(): void
    {
        $total = $this->stocks()->sum('quantity');
        $this->update([
            'current_occupancy' => $total,
        ]);
    }

    /**
     * Méthode : Définir comme entrepôt par défaut
     * 
     * @return void
     */
    public function setAsDefault(): void
    {
        self::where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}