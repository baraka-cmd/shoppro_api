<?php

namespace App\Models\Review;

use Illuminate\Database\Eloquent\Model;

class ReviewImage extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'review_images';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'review_id',
        'image_path',
        'thumbnail_path',
        'alt_text',
        'caption',
        'width',
        'height',
        'file_size',
        'type',
        'position',
        'is_active',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'file_size' => 'integer',
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relation : L'image appartient à un avis
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function review()
    {
        return $this->belongsTo(Review::class);
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
        return asset('images/default-review.png');
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
     * Scope : Images actives
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Images triées
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc');
    }
}