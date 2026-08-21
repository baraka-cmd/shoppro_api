<?php

namespace App\Models\Review;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Catalog\Product;

class Review extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'reviews';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'rating',
        'quality_rating',
        'price_rating',
        'shipping_rating',
        'title',
        'content',
        'is_verified_purchase',
        'is_recommended',
        'helpful_count',
        'not_helpful_count',
        'views_count',
        'status',
        'admin_response',
        'admin_response_at',
        'approved_at',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'quality_rating' => 'integer',
        'price_rating' => 'integer',
        'shipping_rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'is_recommended' => 'boolean',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'views_count' => 'integer',
        'admin_response_at' => 'datetime',
        'approved_at' => 'datetime',
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
        'admin_response_at',
        'approved_at',
    ];

    /**
     * Relation : L'avis appartient à un utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : L'avis concerne un produit
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relation : L'avis est lié à une commande (optionnel)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(\App\Models\Order\Order::class);
    }

    /**
     * Relation : Images de l'avis
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany(ReviewImage::class);
    }

    /**
     * Relation : Votes sur l'avis
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function votes()
    {
        return $this->hasMany(ReviewVote::class);
    }

    /**
     * Accesseur : Statut en français
     * 
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
            'spam' => 'Spam',
            default => $this->status,
        };
    }

    /**
     * Accesseur : Note formatée
     * 
     * @return string
     */
    public function getRatingFormattedAttribute(): string
    {
        return number_format($this->rating, 1) . '/5';
    }

    /**
     * Accesseur : Taux d'utilité
     * 
     * @return float
     */
    public function getHelpfulnessRateAttribute(): float
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        
        if ($total <= 0) {
            return 0;
        }
        
        return round(($this->helpful_count / $total) * 100, 1);
    }

    /**
     * Scope : Avis approuvés
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope : Avis en attente
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope : Avis vérifiés
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope : Avis par note
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $rating
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Méthode : Approuver l'avis
     * 
     * @return void
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        
        // Mettre à jour les statistiques du produit
        $this->product->updateStats();
    }

    /**
     * Méthode : Rejeter l'avis
     * 
     * @return void
     */
    public function reject(): void
    {
        $this->update([
            'status' => 'rejected',
        ]);
    }

    /**
     * Méthode : Répondre à l'avis (admin)
     * 
     * @param string $response
     * @return void
     */
    public function respond(string $response): void
    {
        $this->update([
            'admin_response' => $response,
            'admin_response_at' => now(),
        ]);
    }
}