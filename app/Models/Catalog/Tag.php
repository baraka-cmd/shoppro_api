<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'tags';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'color',
        'background_color',
        'icon',
        'description',
        'products_count',
        'is_active',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'products_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relation : Produits avec ce tag (Many-to-Many)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tag')
                    ->withTimestamps()
                    ->withPivot('position');
    }

    /**
     * Accesseur : Style CSS complet du tag
     * 
     * @return string
     */
    public function getStyleAttribute(): string
    {
        $styles = [];
        
        if ($this->color) {
            $styles[] = "color: {$this->color}";
        }
        if ($this->background_color) {
            $styles[] = "background-color: {$this->background_color}";
        }
        
        return implode('; ', $styles);
    }

    /**
     * Accesseur : Nom formaté avec le # si couleur
     * 
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->color && str_starts_with($this->color, '#')) {
            return $this->name;
        }
        return $this->name;
    }

    /**
     * Scope : Tags actifs
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Tags avec produits
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasProducts($query)
    {
        return $query->where('products_count', '>', 0);
    }

    /**
     * Scope : Tags triés par nombre de produits
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMostUsed($query)
    {
        return $query->orderBy('products_count', 'desc');
    }

    /**
     * Méthode : Mettre à jour le nombre de produits
     * 
     * @return void
     */
    public function updateProductsCount(): void
    {
        $this->update([
            'products_count' => $this->products()->count(),
        ]);
    }

    /**
     * Méthode : Attacher des produits au tag
     * 
     * @param array|int $productIds
     * @return void
     */
    public function attachProducts(array|int $productIds): void
    {
        $this->products()->syncWithoutDetaching((array) $productIds);
        $this->updateProductsCount();
    }

    /**
     * Méthode : Détacher des produits du tag
     * 
     * @param array|int $productIds
     * @return void
     */
    public function detachProducts(array|int $productIds): void
    {
        $this->products()->detach((array) $productIds);
        $this->updateProductsCount();
    }
}