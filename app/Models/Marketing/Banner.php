<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'banners';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'image',
        'image_mobile',
        'thumbnail',
        'target_type',
        'target_id',
        'external_url',
        'position',
        'sort_order',
        'start_date',
        'end_date',
        'is_active',
        'is_animated',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_animated' => 'boolean',
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
        'start_date',
        'end_date',
    ];

    /**
     * Accesseur : Type de cible en français
     * 
     * @return string
     */
    public function getTargetTypeLabelAttribute(): string
    {
        return match($this->target_type) {
            'product' => 'Produit',
            'category' => 'Catégorie',
            'promotion' => 'Promotion',
            'external_url' => 'URL externe',
            'none' => 'Aucun',
            default => $this->target_type,
        };
    }

    /**
     * Accesseur : Position en français
     * 
     * @return string
     */
    public function getPositionLabelAttribute(): string
    {
        return match($this->position) {
            'home_top' => 'Haut de page d\'accueil',
            'home_middle' => 'Milieu de page',
            'home_bottom' => 'Bas de page',
            'category_page' => 'Page catégorie',
            'product_page' => 'Page produit',
            'sidebar' => 'Barre latérale',
            default => $this->position,
        };
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
        return asset('images/default-banner.png');
    }

    /**
     * Accesseur : URL de l'image mobile
     * 
     * @return string
     */
    public function getMobileImageUrlAttribute(): string
    {
        if ($this->image_mobile) {
            return asset('storage/' . $this->image_mobile);
        }
        return $this->image_url;
    }

    /**
     * Accesseur : La bannière est-elle visible ?
     * 
     * @return bool
     */
    public function getIsVisibleAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        if ($this->start_date && now()->lt($this->start_date)) {
            return false;
        }
        
        if ($this->end_date && now()->gt($this->end_date)) {
            return false;
        }
        
        return true;
    }

    /**
     * Accesseur : URL de destination
     * 
     * @return string|null
     */
    public function getDestinationUrlAttribute(): ?string
    {
        return match($this->target_type) {
            'external_url' => $this->external_url,
            'product' => '/product/' . $this->target_id,
            'category' => '/category/' . $this->target_id,
            'promotion' => '/promotion/' . $this->target_id,
            default => null,
        };
    }

    /**
     * Scope : Bannières actives
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Bannières visibles (date)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisible($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('start_date')
                           ->orWhere('start_date', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', now());
                     });
    }

    /**
     * Scope : Bannières par position
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $position
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope : Bannières triées
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}