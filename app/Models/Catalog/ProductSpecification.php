<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;

class ProductSpecification extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'product_specifications';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'label',
        'value',
        'group',
        'position',
        'icon',
        'is_highlighted',
        'is_active',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'position' => 'integer',
        'is_highlighted' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relation : La spécification appartient à un produit
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accesseur : Groupe en français
     * 
     * @return string
     */
    public function getGroupLabelAttribute(): string
    {
        return match($this->group) {
            'performance' => 'Performance',
            'display' => 'Affichage',
            'battery' => 'Batterie',
            'camera' => 'Caméra',
            'storage' => 'Stockage',
            'connectivity' => 'Connectivité',
            'dimensions' => 'Dimensions',
            'weight' => 'Poids',
            'material' => 'Matière',
            'other' => 'Autre',
            default => $this->group ?? 'Général',
        };
    }

    /**
     * Scope : Spécifications actives
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Spécifications mises en avant
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighlighted($query)
    {
        return $query->where('is_highlighted', true);
    }

    /**
     * Scope : Spécifications par groupe
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope : Spécifications triées
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc')
                     ->orderBy('id', 'asc');
    }

    /**
     * Méthode : Récupérer les spécifications groupées
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getGrouped(int $productId)
    {
        return self::where('product_id', $productId)
                   ->active()
                   ->ordered()
                   ->get()
                   ->groupBy('group');
    }
}