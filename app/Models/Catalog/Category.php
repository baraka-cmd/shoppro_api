<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'categories';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'icon',
        'parent_id',
        'position',
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
        'position' => 'integer',
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
     * Relation : Catégorie parente (auto-référencement)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Relation : Sous-catégories (enfants)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Relation : Tous les descendants (récursif)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Relation : Sous-catégories associées
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }

    /**
     * Relation : Produits de cette catégorie
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Accesseur : Nombre total de produits (directs + sous-catégories)
     * 
     * @return int
     */
    public function getTotalProductsCountAttribute(): int
    {
        $directCount = $this->products()->count();
        $subcategoryCount = $this->subcategories()
                                ->withCount('products')
                                ->get()
                                ->sum('products_count');
        
        return $directCount + $subcategoryCount;
    }

    /**
     * Accesseur : URL de l'image
     * 
     * @return string
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return asset('images/default-category.png');
    }

    /**
     * Scope : Catégories racines (sans parent)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope : Catégories actives uniquement
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Catégories mises en avant
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope : Catégories triées par position
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc')
                     ->orderBy('name', 'asc');
    }

    /**
     * Méthode : Récupérer l'arborescence complète
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getTree()
    {
        return self::root()
                   ->with('descendants')
                   ->ordered()
                   ->get();
    }
}