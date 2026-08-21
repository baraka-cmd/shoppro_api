<?php

namespace App\Models\Config;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'countries';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'iso3',
        'phone_code',
        'flag',
        'flag_emoji',
        'currency_code',
        'currency_name',
        'currency_symbol',
        'language_code',
        'is_delivery_available',
        'delivery_fee',
        'delivery_days',
        'latitude',
        'longitude',
        'is_active',
        'is_default',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'is_delivery_available' => 'boolean',
        'delivery_fee' => 'float',
        'delivery_days' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
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
     * Relation : Villes de ce pays
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cities()
    {
        return $this->hasMany(City::class);
    }

    /**
     * Accesseur : URL du drapeau
     * 
     * @return string
     */
    public function getFlagUrlAttribute(): string
    {
        if ($this->flag) {
            return asset('storage/' . $this->flag);
        }
        return asset('images/flags/' . strtolower($this->code) . '.png');
    }

    /**
     * Accesseur : Nom complet avec drapeau
     * 
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        $emoji = $this->flag_emoji ? $this->flag_emoji . ' ' : '';
        return $emoji . $this->name . ' (' . $this->phone_code . ')';
    }

    /**
     * Accesseur : Devise formatée
     * 
     * @return string
     */
    public function getCurrencyDisplayAttribute(): string
    {
        return $this->currency_name . ' (' . $this->currency_symbol . ')';
    }

    /**
     * Méthode : Définir comme pays par défaut
     * 
     * @return void
     */
    public function setAsDefault(): void
    {
        self::where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Méthode : Récupérer le pays par défaut
     * 
     * @return Country|null
     */
    public static function getDefault(): ?Country
    {
        return self::where('is_default', true)->first() 
               ?? self::where('is_active', true)->first();
    }

    /**
     * Scope : Pays actifs
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Pays avec livraison disponible
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeliveryAvailable($query)
    {
        return $query->where('is_delivery_available', true);
    }
}