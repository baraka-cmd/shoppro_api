<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Faq extends Model
{
    use SoftDeletes;

    /**
     * Les attributs assignables en masse.
     *
     * @var array<string>
     */
    protected $fillable = [
        'faq_category_id',
        'question',
        'answer',
        'slug',
        'is_active',
        'is_popular',
        'sort_order',
        'views_count',
        'helpful_count',
    ];

    /**
     * Les attributs à caster.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'sort_order' => 'integer',
        'views_count' => 'integer',
        'helpful_count' => 'integer',
    ];

    /**
     * Boot du modèle.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Faq $faq) {
            if (empty($faq->slug)) {
                $faq->slug = Str::slug($faq->question);
            }
        });

        static::updating(function (Faq $faq) {
            if (empty($faq->slug)) {
                $faq->slug = Str::slug($faq->question);
            }
        });
    }

    /**
     * La catégorie de cette FAQ.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }

    /**
     * Scope : FAQs actives.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : FAQs populaires.
     */
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Scope : triées par ordre.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')
                     ->orderBy('created_at', 'desc');
    }

    /**
     * Incrémenter le compteur de vues.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Incrémenter le compteur d'aide.
     */
    public function incrementHelpful(): void
    {
        $this->increment('helpful_count');
    }
}