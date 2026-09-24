<?php

namespace App\Models\Legal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LegalDocument extends Model
{
    use SoftDeletes;

    /**
     * Types de documents disponibles.
     */
    public const TYPE_TERMS = 'terms_conditions';
    public const TYPE_PRIVACY = 'privacy_policy';
    public const TYPE_REFUND = 'refund_policy';
    public const TYPE_SHIPPING = 'shipping_policy';
    public const TYPE_ABOUT = 'about';
    public const TYPE_OTHER = 'other';

    /**
     * Les attributs assignables en masse.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'type',
        'content',
        'version',
        'published_at',
        'is_active',
    ];

    /**
     * Les attributs à caster.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Boot du modèle.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (LegalDocument $document) {
            if (empty($document->slug)) {
                $document->slug = Str::slug($document->title);
            }
        });

        static::updating(function (LegalDocument $document) {
            if (empty($document->slug)) {
                $document->slug = Str::slug($document->title);
            }
        });
    }

    /**
     * Scope : documents actifs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : documents publiés.
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    /**
     * Scope : par type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Récupérer les conditions d'utilisation.
     */
    public static function getTermsConditions(): ?self
    {
        return self::active()
                   ->published()
                   ->byType(self::TYPE_TERMS)
                   ->latest()
                   ->first();
    }

    /**
     * Récupérer la politique de confidentialité.
     */
    public static function getPrivacyPolicy(): ?self
    {
        return self::active()
                   ->published()
                   ->byType(self::TYPE_PRIVACY)
                   ->latest()
                   ->first();
    }

    /**
     * Récupérer la politique de remboursement.
     */
    public static function getRefundPolicy(): ?self
    {
        return self::active()
                   ->published()
                   ->byType(self::TYPE_REFUND)
                   ->latest()
                   ->first();
    }

    /**
     * Récupérer la politique de livraison.
     */
    public static function getShippingPolicy(): ?self
    {
        return self::active()
                   ->published()
                   ->byType(self::TYPE_SHIPPING)
                   ->latest()
                   ->first();
    }

    /**
     * Récupérer la page À propos.
     */
    public static function getAbout(): ?self
    {
        return self::active()
                   ->published()
                   ->byType(self::TYPE_ABOUT)
                   ->latest()
                   ->first();
    }

    /**
     * Le document est-il publié ?
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at <= now();
    }
}