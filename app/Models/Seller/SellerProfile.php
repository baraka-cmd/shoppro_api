<?php

namespace App\Models\Seller;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * Modèle : SellerProfile
 *
 * Représente le profil étendu d'un vendeur (boutique).
 * Relation 1-to-1 stricte avec `User` (via user_id unique).
 *
 * Responsabilités :
 *   - Identité publique de la boutique
 *   - Statut KYC (vérification d'identité)
 *   - Configuration financière (commission, balance)
 *   - Statistiques dénormalisées (perf dashboard)
 *   - Configuration payout (mobile money, banque)
 *
 * ⚠️ Règles métier critiques :
 *   - Un vendeur ne peut publier des produits que si :
 *       kyc_status = 'verified' ET is_active = true
 *   - Un vendeur ne peut demander un payout que si :
 *       balance > 0 ET aucun payout 'pending'/'processing' en cours
 *   - Le profil n'est JAMAIS supprimé physiquement (traçabilité financière)
 *     → softDeletes + is_active = false pour désactiver
 *
 * Cycle de vie :
 *   pending → verified → (actif) → is_active = false (désactivé)
 *          ↘ rejected (KYC refusé)
 *
 * Relations :
 *   - BelongsTo  User
 *   - HasMany    SellerTransaction
 *   - HasMany    SellerPayout
 *   - HasMany    Product (via products.seller_id)
 *   - HasMany    Review (via reviews.seller_id)
 *   - HasMany    OrderItem (via order_items.seller_id)
 *   - HasMany    Stock (via stocks.seller_id)
 *   - HasMany    Promotion (via promotions.seller_id)
 */
class SellerProfile extends Model
{
    use HasFactory, SoftDeletes;

    // ============================================
    // CONSTANTES MÉTIER
    // ============================================

    const KYC_PENDING  = 'pending';
    const KYC_VERIFIED = 'verified';
    const KYC_REJECTED = 'rejected';

    const PAYOUT_METHOD_MOBILE_MONEY = 'mobile_money';
    const PAYOUT_METHOD_BANK         = 'bank_transfer';
    const PAYOUT_METHOD_PAYPAL       = 'paypal';
    const PAYOUT_METHOD_CASH         = 'cash';

    // ============================================
    // CONFIGURATION ELOQUENT
    // ============================================

    protected $table = 'seller_profiles';

    protected $fillable = [
        'user_id',
        'shop_name',
        'shop_slug',
        'description',
        'logo',
        'banner',
        'phone',
        'email',
        'address',
        'city',
        'country',
        'kyc_status',
        'kyc_verified_at',
        'kyc_rejection_reason',
        'commission_rate',
        'balance',
        'pending_balance',
        'payout_method',
        'payout_account',
        'products_count',
        'orders_count',
        'sales_count',
        'total_revenue',
        'average_rating',
        'reviews_count',
        'is_active',
        'is_featured',
        'is_verified',
        'onboarding_completed_at',
        'onboarding_dismissed_at',
        'onboarding_steps',
    ];

    protected $casts = [
        'commission_rate' => 'float',
        'balance'         => 'float',
        'pending_balance' => 'float',
        'total_revenue'   => 'float',
        'average_rating'  => 'float',
        'products_count'  => 'integer',
        'orders_count'    => 'integer',
        'sales_count'     => 'integer',
        'reviews_count'   => 'integer',
        'is_active'       => 'boolean',
        'is_featured'     => 'boolean',
        'is_verified'     => 'boolean',
        'kyc_verified_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'onboarding_dismissed_at' => 'datetime',
        'onboarding_steps'        => 'array',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'kyc_verified_at',
        'onboarding_completed_at',
        'onboarding_dismissed_at',
    ];

    protected $hidden = [];

    protected $appends = [
        'logo_url',
        'banner_url',
        'status_label',
        'kyc_status_label',
        'balance_formatted',
        'pending_balance_formatted',
        'total_revenue_formatted',
        'average_rating_formatted',
    ];

    // ============================================
    // BOOT — Auto-génération du slug
    // ============================================

    protected static function booted(): void
    {
        static::creating(function (SellerProfile $profile) {
            if (empty($profile->shop_slug)) {
                $profile->shop_slug = self::generateUniqueSlug(
                    $profile->shop_name ?? 'boutique'
                );
            }
        });
    }

    // ============================================
    // RELATIONS
    // ============================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(SellerTransaction::class, 'seller_id', 'user_id');
    }

    public function payouts()
    {
        return $this->hasMany(SellerPayout::class, 'seller_id', 'user_id');
    }

    public function products()
    {
        return $this->hasMany(\App\Models\Catalog\Product::class, 'seller_id', 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(\App\Models\Review\Review::class, 'seller_id', 'user_id');
    }

    public function orderItems()
    {
        return $this->hasMany(\App\Models\Order\OrderItem::class, 'seller_id', 'user_id');
    }

    public function stocks()
    {
        return $this->hasMany(\App\Models\Catalog\Stock::class, 'seller_id', 'user_id');
    }

    public function promotions()
    {
        return $this->hasMany(\App\Models\Marketing\Promotion::class, 'seller_id', 'user_id');
    }

    // ============================================
    // ACCESSORS
    // ============================================

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }
        return Str::startsWith($this->logo, ['http://', 'https://'])
            ? $this->logo
            : asset('storage/' . $this->logo);
    }

    public function getBannerUrlAttribute(): ?string
    {
        if (!$this->banner) {
            return null;
        }
        return Str::startsWith($this->banner, ['http://', 'https://'])
            ? $this->banner
            : asset('storage/' . $this->banner);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Actif' : 'Inactif';
    }

    public function getKycStatusLabelAttribute(): string
    {
        return match ($this->kyc_status) {
            self::KYC_PENDING  => 'En attente',
            self::KYC_VERIFIED => 'Vérifié',
            self::KYC_REJECTED => 'Refusé',
            default            => $this->kyc_status,
        };
    }

    public function getBalanceFormattedAttribute(): string
    {
        return number_format($this->balance, 2, ',', ' ') . ' FCFA';
    }

    public function getPendingBalanceFormattedAttribute(): string
    {
        return number_format($this->pending_balance, 2, ',', ' ') . ' FCFA';
    }

    public function getTotalRevenueFormattedAttribute(): string
    {
        return number_format($this->total_revenue, 2, ',', ' ') . ' FCFA';
    }

    public function getAverageRatingFormattedAttribute(): string
    {
        return number_format($this->average_rating, 1) . '/5';
    }

    // ============================================
    // GETTERS MÉTIER
    // ============================================

    public function isKycVerified(): bool
    {
        return $this->kyc_status === self::KYC_VERIFIED;
    }

    public function isKycPending(): bool
    {
        return $this->kyc_status === self::KYC_PENDING;
    }

    public function isKycRejected(): bool
    {
        return $this->kyc_status === self::KYC_REJECTED;
    }

    /**
     * Le vendeur peut-il publier des produits ?
     * Condition : KYC validé + profil actif + non soft-deleted
     */
    public function canPublishProducts(): bool
    {
        return $this->is_active
            && $this->isKycVerified()
            && !$this->trashed();
    }

    /**
     * Le vendeur peut-il demander un payout ?
     * Condition : solde disponible > 0 + aucun payout en cours
     */
    public function canRequestPayout(): bool
    {
        if (!$this->is_active || !$this->isKycVerified()) {
            return false;
        }

        if ($this->balance <= 0) {
            return false;
        }

        // Un seul payout en cours à la fois
        return !$this->payouts()
            ->whereIn('status', [
                SellerPayout::STATUS_PENDING,
                SellerPayout::STATUS_PROCESSING,
            ])
            ->exists();
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('kyc_status', self::KYC_VERIFIED);
    }

    public function scopePending($query)
    {
        return $query->where('kyc_status', self::KYC_PENDING);
    }

    public function scopeRejected($query)
    {
        return $query->where('kyc_status', self::KYC_REJECTED);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeWithBalance($query)
    {
        return $query->where('balance', '>', 0);
    }

    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    // ============================================
    // MÉTHODES MÉTIER
    // ============================================

    /**
     * Valide le KYC du vendeur.
     */
    public function verifyKyc(): void
    {
        $this->update([
            'kyc_status'           => self::KYC_VERIFIED,
            'kyc_verified_at'      => now(),
            'kyc_rejection_reason' => null,
        ]);
    }

    /**
     * Rejette le KYC avec une raison.
     */
    public function rejectKyc(string $reason): void
    {
        $this->update([
            'kyc_status'           => self::KYC_REJECTED,
            'kyc_verified_at'      => null,
            'kyc_rejection_reason' => $reason,
        ]);
    }

    /**
     * Remet le KYC en attente (re-soumission).
     */
    public function resetKyc(): void
    {
        $this->update([
            'kyc_status'           => self::KYC_PENDING,
            'kyc_verified_at'      => null,
            'kyc_rejection_reason' => null,
        ]);
    }

    /**
     * Crédite le solde disponible (vente livrée).
     */
    public function creditBalance(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    /**
     * Débite le solde disponible (payout complété).
     */
    public function debitBalance(float $amount): bool
    {
        if ($this->balance < $amount) {
            return false;
        }

        $this->decrement('balance', $amount);
        return true;
    }

    /**
     * Déplace un montant du pending vers le disponible.
     */
    public function moveFromPendingToBalance(float $amount): bool
    {
        if ($this->pending_balance < $amount) {
            return false;
        }

        $this->decrement('pending_balance', $amount);
        $this->increment('balance', $amount);
        return true;
    }

    /**
     * Recalcule les statistiques dénormalisées.
     * À appeler via un Observer ou un job planifié.
     */
    public function refreshStats(): void
    {
        $products = $this->products()->count();
        $orders   = $this->orderItems()->distinct('order_id')->count('order_id');
        $sales    = $this->orderItems()->where('item_status', 'delivered')->sum('quantity');
        $revenue  = $this->orderItems()->where('item_status', 'delivered')->sum('total_price');
        $reviews  = $this->reviews()->where('status', 'approved')->count();
        $avg      = $this->reviews()->where('status', 'approved')->avg('rating') ?? 0;

        $this->update([
            'products_count' => $products,
            'orders_count'   => $orders,
            'sales_count'    => $sales,
            'total_revenue'  => $revenue,
            'reviews_count'  => $reviews,
            'average_rating' => round($avg, 2),
        ]);
    }

    // ============================================
    // HELPERS PRIVÉS
    // ============================================

    /**
     * Génère un slug unique et stable pour la boutique.
     */
    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 2;

        while (self::withTrashed()->where('shop_slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

        // ============================================
    // ONBOARDING — MÉTHODES
    // ============================================

    /**
     * La checklist d'onboarding doit-elle être affichée ?
     *
     * Affichée si :
     *   - Jamais complétée (onboarding_completed_at = null)
     *   - ET jamais masquée (onboarding_dismissed_at = null)
     */
    public function shouldShowOnboarding(): bool
    {
        return $this->onboarding_completed_at === null
            && $this->onboarding_dismissed_at === null;
    }

    /**
     * L'onboarding est-il complété ?
     */
    public function isOnboardingCompleted(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    /**
     * Marquer une étape comme complétée.
     *
     * @param string $step  : profile_completed | kyc_validated |
     *                        first_product_published | first_order_received |
     *                        first_order_shipped
     * @return void
     */
    public function completeOnboardingStep(string $step): void
    {
        $steps = $this->onboarding_steps ?? [];

        // Ne rien faire si déjà complétée
        if (!empty($steps[$step])) {
            return;
        }

        $steps[$step] = now()->toIso8601String();

        $data = ['onboarding_steps' => $steps];

        // Si toutes les étapes sont complétées, marquer la date
        $allSteps = [
            'profile_completed',
            'kyc_validated',
            'first_product_published',
            'first_order_received',
            'first_order_shipped',
        ];

        $allCompleted = collect($allSteps)->every(fn ($s) => !empty($steps[$s]));

        if ($allCompleted && $this->onboarding_completed_at === null) {
            $data['onboarding_completed_at'] = now();
        }

        $this->update($data);
    }

    /**
     * Marquer l'onboarding comme masqué manuellement par le vendeur.
     */
    public function dismissOnboarding(): void
    {
        $this->update(['onboarding_dismissed_at' => now()]);
    }

    /**
     * Retourne la progression de l'onboarding (0 à 5).
     */
    public function getOnboardingProgressAttribute(): int
    {
        $steps = $this->onboarding_steps ?? [];

        $allSteps = [
            'profile_completed',
            'kyc_validated',
            'first_product_published',
            'first_order_received',
            'first_order_shipped',
        ];

        return collect($allSteps)->filter(fn ($s) => !empty($steps[$s]))->count();
    }
    
}