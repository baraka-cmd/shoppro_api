<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'products';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'price',
        'discount_price',
        'cost_price',
        'tax_rate',
        'stock',
        'min_stock',
        'max_stock',
        'sku',
        'barcode',
        'category_id',
        'subcategory_id',
        'brand_id',
        'main_image',
        'views_count',
        'sales_count',
        'favorites_count',
        'average_rating',
        'reviews_count',
        'is_active',
        'is_featured',
        'is_new',
        'is_bestseller',
        'is_on_sale',
        'is_digital',
        'available_from',
        'available_until',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'float',
        'discount_price' => 'float',
        'cost_price' => 'float',
        'tax_rate' => 'float',
        'stock' => 'integer',
        'min_stock' => 'integer',
        'max_stock' => 'integer',
        'views_count' => 'integer',
        'sales_count' => 'integer',
        'favorites_count' => 'integer',
        'average_rating' => 'float',
        'reviews_count' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_bestseller' => 'boolean',
        'is_on_sale' => 'boolean',
        'is_digital' => 'boolean',
        'available_from' => 'date',
        'available_until' => 'date',
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
        'available_from',
        'available_until',
    ];

    /**
     * Relation : Le produit appartient à une catégorie
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relation : Le produit appartient à une sous-catégorie
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    /**
     * Relation : Le produit appartient à une marque
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Relation : Images du produit
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Relation : Variantes du produit
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Relation : Attributs du produit
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    /**
     * Relation : Spécifications du produit
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function specifications()
    {
        return $this->hasMany(ProductSpecification::class);
    }

    /**
     * Relation : Tags du produit (Many-to-Many)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tag')
                    ->withTimestamps()
                    ->withPivot('position');
    }

    /**
     * Relation : Stocks du produit
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Relation : Avis du produit
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews()
    {
        return $this->hasMany(\App\Models\Review\Review::class);
    }

    /**
     * Relation : Favoris du produit
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function favorites()
    {
        return $this->hasMany(\App\Models\Review\Favorite::class);
    }

    /**
     * Accesseur : Prix actuel (avec réduction si applicable)
     * 
     * @return float
     */
    public function getCurrentPriceAttribute(): float
    {
        return $this->discount_price ?? $this->price;
    }

    /**
     * Accesseur : Pourcentage de réduction
     * 
     * @return int|null
     */
    public function getDiscountPercentageAttribute(): ?int
    {
        if ($this->discount_price && $this->price > 0) {
            return (int) round((($this->price - $this->discount_price) / $this->price) * 100);
        }
        return null;
    }

    /**
     * Accesseur : URL de l'image principale
     * 
     * @return string
     */
    public function getMainImageUrlAttribute(): string
    {
        if ($this->main_image) {
            return asset('storage/' . $this->main_image);
        }
        return asset('images/default-product.png');
    }

    /**
     * Accesseur : Le produit est-il en stock ?
     * 
     * @return bool
     */
    public function getIsInStockAttribute(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Accesseur : Le produit est-il disponible ?
     * 
     * @return bool
     */
    public function getIsAvailableAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        if ($this->available_from && now()->lt($this->available_from)) {
            return false;
        }
        
        if ($this->available_until && now()->gt($this->available_until)) {
            return false;
        }
        
        return $this->stock > 0;
    }

    /**
     * Scope : Produits actifs
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Produits en stock
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Scope : Produits en vedette
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope : Produits nouveaux
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNew($query)
    {
        return $query->where('is_new', true);
    }

    /**
     * Scope : Produits en promotion
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnSale($query)
    {
        return $query->where('is_on_sale', true)
                     ->whereNotNull('discount_price');
    }

    /**
     * Scope : Produits best-sellers
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBestseller($query)
    {
        return $query->where('is_bestseller', true)
                     ->orderBy('sales_count', 'desc');
    }

    /**
     * Scope : Produits par catégorie
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $categoryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope : Produits par marque
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $brandId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByBrand($query, int $brandId)
    {
        return $query->where('brand_id', $brandId);
    }

    /**
     * Scope : Recherche par nom ou description
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('short_description', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%")
              ->orWhere('sku', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope : Tri par prix
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSortByPrice($query, string $direction = 'asc')
    {
        return $query->orderBy('price', $direction);
    }

    /**
     * Méthode : Décrémenter le stock
     * 
     * @param int $quantity
     * @return bool
     */
    public function decrementStock(int $quantity): bool
    {
        if ($this->stock < $quantity) {
            return false;
        }
        
        $this->decrement('stock', $quantity);
        return true;
    }

    /**
     * Méthode : Incrémenter le stock
     * 
     * @param int $quantity
     * @return void
     */
    public function incrementStock(int $quantity): void
    {
        $this->increment('stock', $quantity);
    }

    /**
     * Méthode : Mettre à jour les statistiques
     * 
     * @return void
     */
    public function updateStats(): void
    {
        $this->update([
            'favorites_count' => $this->favorites()->count(),
            'reviews_count' => $this->reviews()->where('status', 'approved')->count(),
            'average_rating' => $this->reviews()->where('status', 'approved')->avg('rating') ?? 0,
        ]);
    }
}