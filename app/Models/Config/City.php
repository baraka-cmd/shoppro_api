<?php

namespace App\Models\Config;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'cities';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'country_id',
        'name',
        'code',
        'postal_code',
        'latitude',
        'longitude',
        'is_delivery_available',
        'delivery_fee',
        'delivery_days',
        'minimum_order',
        'population',
        'is_active',
        'is_popular',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_delivery_available' => 'boolean',
        'delivery_fee' => 'float',
        'delivery_days' => 'integer',
        'minimum_order' => 'float',
        'population' => 'integer',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
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
     * Relation : La ville appartient à un pays
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Accesseur : Nom complet avec pays
     * 
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return $this->name . ', ' . $this->country->name;
    }

    /**
     * Accesseur : Frais de livraison effectifs
     * 
     * @return float
     */
    public function getEffectiveDeliveryFeeAttribute(): float
    {
        return $this->delivery_fee > 0 ? $this->delivery_fee : $this->country->delivery_fee;
    }

    /**
     * Accesseur : Délai de livraison effectif
     * 
     * @return int
     */
    public function getEffectiveDeliveryDaysAttribute(): int
    {
        return $this->delivery_days ?? $this->country->delivery_days;
    }

    /**
     * Accesseur : Population formatée
     * 
     * @return string
     */
    public function getPopulationFormattedAttribute(): string
    {
        if (!$this->population) {
            return 'N/A';
        }
        return number_format($this->population, 0, '', ' ');
    }

    /**
     * Scope : Villes actives
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Villes populaires
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Scope : Villes avec livraison
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeliveryAvailable($query)
    {
        return $query->where('is_delivery_available', true)
                     ->whereHas('country', function ($q) {
                         $q->where('is_delivery_available', true);
                     });
    }

    /**
     * Scope : Villes par pays
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $countryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCountry($query, int $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope : Villes triées par nom
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_popular', 'desc')
                     ->orderBy('name', 'asc');
    }
}