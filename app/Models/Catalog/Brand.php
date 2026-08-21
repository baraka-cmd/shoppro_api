<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'brands';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'banner',
        'website',
        'email',
        'phone',
        'country',
        'products_count',
        'average_rating',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'products_count' => 'integer',
        'average_rating' => 'float',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
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
     * Relation : Produits de cette marque
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Accesseur : URL du logo
     * 
     * @return string
     */
    public function getLogoUrlAttribute(): string
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }
        return asset('images/default-brand.png');
    }

    /**
     * Accesseur : URL de la bannière
     * 
     * @return string
     */
    public function getBannerUrlAttribute(): string
    {
        if ($this->banner) {
            return asset('storage/' . $this->banner);
        }
        return asset('images/default-banner.png');
    }

    /**
     * Accesseur : Note moyenne formatée
     * 
     * @return string
     */
    public function getAverageRatingFormattedAttribute(): string
    {
        return number_format($this->average_rating, 1);
    }

    /**
     * Scope : Marques actives
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Marques mises en avant
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope : Marques avec produits
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasProducts($query)
    {
        return $query->where('products_count', '>', 0);
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
     * Méthode : Mettre à jour la note moyenne
     * 
     * @return void
     */
    public function updateAverageRating(): void
    {
        $average = $this->products()
                        ->whereNotNull('average_rating')
                        ->avg('average_rating');
        
        $this->update([
            'average_rating' => $average ?? 0,
        ]);
    }
}