<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class OrderStatusHistory extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'order_status_histories';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'old_status',
        'new_status',
        'note',
        'reason',
        'location',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [];

    /**
     * Relation : L'historique appartient à une commande
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relation : L'historique a été créé par un utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accesseur : Ancien statut en français
     * 
     * @return string
     */
    public function getOldStatusLabelAttribute(): string
    {
        if (!$this->old_status) {
            return 'Aucun';
        }

        return match($this->old_status) {
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'processing' => 'En préparation',
            'shipped' => 'Expédiée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
            'refunded' => 'Remboursée',
            'returned' => 'Retournée',
            default => $this->old_status,
        };
    }

    /**
     * Accesseur : Nouveau statut en français
     * 
     * @return string
     */
    public function getNewStatusLabelAttribute(): string
    {
        return match($this->new_status) {
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'processing' => 'En préparation',
            'shipped' => 'Expédiée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
            'refunded' => 'Remboursée',
            'returned' => 'Retournée',
            default => $this->new_status,
        };
    }

    /**
     * Accesseur : Description du changement
     * 
     * @return string
     */
    public function getChangeDescriptionAttribute(): string
    {
        if (!$this->old_status) {
            return "Commande créée avec le statut : {$this->new_status_label}";
        }
        
        return "Statut changé de '{$this->old_status_label}' à '{$this->new_status_label}'";
    }

    /**
     * Scope : Historique par commande
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $orderId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope : Historique par statut
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('new_status', $status);
    }

    /**
     * Scope : Historique trié du plus récent au plus ancien
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}