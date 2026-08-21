<?php

namespace App\Models\Review;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ReviewVote extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'review_votes';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'review_id',
        'user_id',
        'vote_type',
        'voted_at',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'voted_at' => 'datetime',
    ];

    /**
     * Dates à traiter comme des dates Carbon
     * 
     * @var array<string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'voted_at',
    ];

    /**
     * Relation : Le vote appartient à un avis
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Relation : Le vote appartient à un utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accesseur : Type de vote en français
     * 
     * @return string
     */
    public function getVoteTypeLabelAttribute(): string
    {
        return match($this->vote_type) {
            'helpful' => 'Utile',
            'not_helpful' => 'Pas utile',
            default => $this->vote_type,
        };
    }

    /**
     * Méthode : Basculer le vote
     * 
     * @return void
     */
    public function toggle(): void
    {
        $newType = $this->vote_type === 'helpful' ? 'not_helpful' : 'helpful';
        
        $this->update([
            'vote_type' => $newType,
            'voted_at' => now(),
        ]);
        
        // Mettre à jour les compteurs de l'avis
        $this->review->update([
            'helpful_count' => $this->review->votes()->where('vote_type', 'helpful')->count(),
            'not_helpful_count' => $this->review->votes()->where('vote_type', 'not_helpful')->count(),
        ]);
    }

    /**
     * Scope : Votes utiles
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHelpful($query)
    {
        return $query->where('vote_type', 'helpful');
    }

    /**
     * Scope : Votes inutiles
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotHelpful($query)
    {
        return $query->where('vote_type', 'not_helpful');
    }
}