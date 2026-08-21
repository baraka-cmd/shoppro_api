<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class TicketMessage extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'ticket_messages';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'attachment',
        'attachment_name',
        'attachment_size',
        'sender_type',
        'is_read',
        'read_at',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'attachment_size' => 'integer',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Dates à traiter comme des dates Carbon
     * 
     * @var array<string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'read_at',
    ];

    /**
     * Relation : Le message appartient à un ticket
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class);
    }

    /**
     * Relation : Le message appartient à un utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accesseur : Type d'expéditeur en français
     * 
     * @return string
     */
    public function getSenderTypeLabelAttribute(): string
    {
        return match($this->sender_type) {
            'user' => 'Client',
            'admin' => 'Support',
            'system' => 'Système',
            default => $this->sender_type,
        };
    }

    /**
     * Accesseur : URL de la pièce jointe
     * 
     * @return string|null
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        if ($this->attachment) {
            return asset('storage/' . $this->attachment);
        }
        return null;
    }

    /**
     * Accesseur : Taille de la pièce jointe formatée
     * 
     * @return string
     */
    public function getAttachmentSizeFormattedAttribute(): string
    {
        if (!$this->attachment_size) {
            return 'N/A';
        }

        $bytes = $this->attachment_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }

    /**
     * Méthode : Marquer comme lu
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
     * Scope : Messages non lus
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope : Messages par expéditeur
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $senderType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySender($query, string $senderType)
    {
        return $query->where('sender_type', $senderType);
    }
}