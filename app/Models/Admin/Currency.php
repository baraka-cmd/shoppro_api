<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'currencies';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'symbol',
        'exchange_rate',
        'decimal_separator',
        'thousand_separator',
        'decimal_places',
        'symbol_position',
        'is_default',
        'is_active',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'exchange_rate' => 'float',
        'decimal_places' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Accesseur : Symbole formaté
     * 
     * @return string
     */
    public function getFormattedSymbolAttribute(): string
    {
        return $this->symbol_position === 'before' 
            ? $this->symbol . ' ' 
            : ' ' . $this->symbol;
    }

    /**
     * Méthode : Formater un montant
     * 
     * @param float $amount
     * @return string
     */
    public function format(float $amount): string
    {
        $formatted = number_format(
            $amount,
            $this->decimal_places,
            $this->decimal_separator,
            $this->thousand_separator
        );

        if ($this->symbol_position === 'before') {
            return $this->symbol . ' ' . $formatted;
        }
        
        return $formatted . ' ' . $this->symbol;
    }

    /**
     * Méthode : Convertir depuis la devise de base
     * 
     * @param float $amount
     * @return float
     */
    public function convertFromBase(float $amount): float
    {
        return $amount * $this->exchange_rate;
    }

    /**
     * Méthode : Convertir vers la devise de base
     * 
     * @param float $amount
     * @return float
     */
    public function convertToBase(float $amount): float
    {
        if ($this->exchange_rate <= 0) {
            return 0;
        }
        return $amount / $this->exchange_rate;
    }

    /**
     * Méthode : Définir comme devise par défaut
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
     * Méthode : Récupérer la devise par défaut
     * 
     * @return Currency|null
     */
    public static function getDefault(): ?Currency
    {
        return self::where('is_default', true)->first() 
               ?? self::where('is_active', true)->first();
    }

    /**
     * Scope : Devises actives
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}