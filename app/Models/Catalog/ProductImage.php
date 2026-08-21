<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'product_images';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'image_path',
        'thumbnail_path',
        'alt_text',
        'caption',
        'position',
        'is_primary',
        'width',
        'height',
        'file_size',
        'type',
        'is_active',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'position' => 'integer',
        'is_primary' => 'boolean',
        'width' => 'integer',
        'height' => 'integer',
        'file_size' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relation : L'image appartient à un produit
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accesseur : URL complète de l'image
     * 
     * @return string
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }
        return asset('images/default-product.png');
    }

    /**
     * Accesseur : URL de la miniature
     * 
     * @return string
     */
    public function getThumbnailUrlAttribute(): string
    {
        if ($this->thumbnail_path) {
            return asset('storage/' . $this->thumbnail_path);
        }
        return $this->image_url;
    }

    /**
     * Accesseur : Taille formatée
     * 
     * @return string
     */
    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }

    /**
     * Scope : Images actives uniquement
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Images principales
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope : Images triées par position
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
     * Méthode : Définir cette image comme principale
     * 
     * @return void
     */
    public function setAsPrimary(): void
    {
        // Désactiver les autres images principales
        self::where('product_id', $this->product_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Activer celle-ci
        $this->update([
            'is_primary' => true,
            'position' => 0,
        ]);
    }
}