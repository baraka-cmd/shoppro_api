<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'product_attributes';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'name',
        'value',
        'unit',
        'icon',
        'color_code',
        'position',
        'is_visible',
        'is_filterable',
        'is_active',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'position' => 'integer',
        'is_visible' => 'boolean',
        'is_filterable' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relation : L'attribut appartient à un produit
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accesseur : Nom formaté (avec unité)
     * 
     * @return string
     */
    public function getFormattedValueAttribute(): string
    {
        if ($this->unit) {
            return $this->value . ' ' . $this->unit;
        }
        return $this->value;
    }

    /**
     * Accesseur : L'attribut est-il une couleur ?
     * 
     * @return bool
     */
    public function getIsColorAttribute(): bool
    {
        return !is_null($this->color_code);
    }

    /**
     * Scope : Attributs visibles
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope : Attributs filtrables
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    /**
     * Scope : Attributs actifs
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Attributs triés par position
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc');
    }
}