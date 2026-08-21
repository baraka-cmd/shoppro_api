<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Notification extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'notifications';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'body',
        'image',
        'icon',
        'type',
        'target_type',
        'target_id',
        'is_read',
        'read_at',
        'sent_push',
        'sent_email',
        'sent_sms',
        'sent_at',
        'received_at',
        'is_active',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'is_read' => 'boolean',
        'sent_push' => 'boolean',
        'sent_email' => 'boolean',
        'sent_sms' => 'boolean',
        'is_active' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
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
        'read_at',
        'sent_at',
        'received_at',
    ];

    /**
     * Relation : La notification appartient à un utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accesseur : Type en français
     * 
     * @return string
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'order' => 'Commande',
            'promotion' => 'Promotion',
            'price_alert' => 'Alerte prix',
            'stock_alert' => 'Alerte stock',
            'system' => 'Système',
            'chat' => 'Message',
            'review' => 'Avis',
            default => $this->type,
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
        return asset('images/default-notification.png');
    }

    /**
     * Méthode : Marquer comme lue
     * 
     * @return void
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Méthode : Marquer comme non lue
     * 
     * @return void
     */
    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Scope : Notifications non lues
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope : Notifications par type
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope : Notifications actives
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}