<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class NewsletterSubscriber extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'newsletter_subscribers';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'name',
        'user_id',
        'status',
        'unsubscribe_token',
        'subscribed_at',
        'unsubscribed_at',
        'last_email_sent_at',
        'emails_sent_count',
        'emails_opened_count',
        'emails_clicked_count',
        'preferences',
        'source',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'last_email_sent_at' => 'datetime',
        'emails_sent_count' => 'integer',
        'emails_opened_count' => 'integer',
        'emails_clicked_count' => 'integer',
        'preferences' => 'array',
    ];

    /**
     * Dates à traiter comme des dates Carbon
     * 
     * @var array<string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'subscribed_at',
        'unsubscribed_at',
        'last_email_sent_at',
    ];

    /**
     * Relation : L'abonné peut être un utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accesseur : Statut en français
     * 
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'subscribed' => 'Abonné',
            'unsubscribed' => 'Désabonné',
            'bounced' => 'Email rejeté',
            'spam' => 'Spam',
            default => $this->status,
        };
    }

    /**
     * Accesseur : Taux d'ouverture
     * 
     * @return float
     */
    public function getOpenRateAttribute(): float
    {
        if ($this->emails_sent_count <= 0) {
            return 0;
        }
        return round(($this->emails_opened_count / $this->emails_sent_count) * 100, 1);
    }

    /**
     * Accesseur : Taux de clic
     * 
     * @return float
     */
    public function getClickRateAttribute(): float
    {
        if ($this->emails_sent_count <= 0) {
            return 0;
        }
        return round(($this->emails_clicked_count / $this->emails_sent_count) * 100, 1);
    }

    /**
     * Méthode : Générer un token de désabonnement
     * 
     * @return string
     */
    public static function generateUnsubscribeToken(): string
    {
        return hash('sha256', uniqid() . microtime());
    }

    /**
     * Méthode : Désabonner
     * 
     * @return void
     */
    public function unsubscribe(): void
    {
        $this->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);
    }

    /**
     * Méthode : Réabonner
     * 
     * @return void
     */
    public function resubscribe(): void
    {
        $this->update([
            'status' => 'subscribed',
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ]);
    }

    /**
     * Scope : Abonnés actifs
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSubscribed($query)
    {
        return $query->where('status', 'subscribed');
    }

    /**
     * Scope : Abonnés désabonnés
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnsubscribed($query)
    {
        return $query->where('status', 'unsubscribed');
    }
}