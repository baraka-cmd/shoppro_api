<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Order\Order;

class SupportTicket extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'support_tickets';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_number',
        'user_id',
        'order_id',
        'subject',
        'description',
        'category',
        'priority',
        'status',
        'assigned_to',
        'resolved_at',
        'closed_at',
        'last_reply_at',
        'satisfaction_rating',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_reply_at' => 'datetime',
        'satisfaction_rating' => 'integer',
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
        'resolved_at',
        'closed_at',
        'last_reply_at',
    ];

    /**
     * Relation : Le ticket appartient à un utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Le ticket concerne une commande (optionnel)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relation : Agent assigné au ticket
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Relation : Messages du ticket
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    /**
     * Accesseur : Statut en français
     * 
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'open' => 'Ouvert',
            'pending' => 'En attente',
            'resolved' => 'Résolu',
            'closed' => 'Fermé',
            'reopened' => 'Réouvert',
            default => $this->status,
        };
    }

    /**
     * Accesseur : Priorité en français
     * 
     * @return string
     */
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'low' => 'Faible',
            'medium' => 'Moyenne',
            'high' => 'Haute',
            'urgent' => 'Urgente',
            default => $this->priority,
        };
    }

    /**
     * Accesseur : Catégorie en français
     * 
     * @return string
     */
    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'order' => 'Commande',
            'payment' => 'Paiement',
            'delivery' => 'Livraison',
            'product' => 'Produit',
            'account' => 'Compte',
            'technical' => 'Technique',
            'other' => 'Autre',
            default => $this->category,
        };
    }

    /**
     * Méthode : Générer un numéro de ticket unique
     * 
     * @return string
     */
    public static function generateTicketNumber(): string
    {
        $prefix = 'TKT';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        
        return $prefix . '-' . $date . '-' . $random;
    }

    /**
     * Méthode : Fermer le ticket
     * 
     * @return void
     */
    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    /**
     * Méthode : Résoudre le ticket
     * 
     * @return void
     */
    public function resolve(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Méthode : Réouvrir le ticket
     * 
     * @return void
     */
    public function reopen(): void
    {
        $this->update([
            'status' => 'reopened',
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    /**
     * Scope : Tickets ouverts
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'pending', 'reopened']);
    }

    /**
     * Scope : Tickets résolus
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope : Tickets par priorité
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $priority
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }
}