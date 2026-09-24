<?php

namespace App\Models\Support;

use App\Models\User;
use App\Models\Order\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    use SoftDeletes;

    /**
     * Les attributs assignables en masse.
     *
     * @var array<string>
     */
    protected $fillable = [
        'ticket_number',
        'user_id',
        'order_id',
        'subject',
        'description',
        'category',
        'status',
        'priority',
        'assigned_to',
        'resolved_at',
        'closed_at',
        'last_reply_at',
        'satisfaction_rating',
    ];

    /**
     * Les attributs à caster.
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
     * Boot du modèle.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SupportTicket $ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
        });
    }

    /**
     * Générer un numéro de ticket unique.
     */
    public static function generateTicketNumber(): string
    {
        $date = now()->format('Ymd');
        $random = Str::upper(Str::random(6));
        $ticketNumber = "SUP-{$date}-{$random}";

        // Vérifier l'unicité
        while (self::where('ticket_number', $ticketNumber)->exists()) {
            $random = Str::upper(Str::random(6));
            $ticketNumber = "SUP-{$date}-{$random}";
        }

        return $ticketNumber;
    }

    /**
     * L'utilisateur propriétaire du ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * La commande liée au ticket (optionnelle).
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Les messages de ce ticket.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class, 'support_ticket_id');
    }

    /**
     * Scope : tickets ouverts.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope : tickets en attente.
     *
     * Dans la base de données, le statut utilisé est "pending".
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope : tickets résolus.
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope : tickets fermés.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Scope : tickets réouverts.
     */
    public function scopeReopened($query)
    {
        return $query->where('status', 'reopened');
    }

    /**
     * Scope : tickets par catégorie.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope : tickets par priorité.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope : tickets pour un utilisateur.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Marquer comme ouvert.
     */
    public function markAsOpen(): void
    {
        $this->update([
            'status' => 'open',
        ]);
    }

    /**
     * Marquer comme en attente.
     */
    public function markAsPending(): void
    {
        $this->update([
            'status' => 'pending',
        ]);
    }

    /**
     * Marquer comme résolu.
     */
    public function markAsResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Marquer comme fermé.
     */
    public function markAsClosed(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    /**
     * Marquer comme réouvert.
     */
    public function markAsReopened(): void
    {
        $this->update([
            'status' => 'reopened',
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    /**
     * Le ticket est-il ouvert ?
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Le ticket est-il en attente ?
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Le ticket est-il résolu ?
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Le ticket est-il fermé ?
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Le ticket est-il réouvert ?
     */
    public function isReopened(): bool
    {
        return $this->status === 'reopened';
    }

    /**
     * Nombre de messages non lus.
     */
    public function getUnreadMessagesCountAttribute(): int
    {
        return $this->messages()
            ->where('is_read', false)
            ->where('sender_type', 'admin')
            ->count();
    }

    /**
     * Dernier message du ticket.
     */
    public function getLastMessageAttribute(): ?TicketMessage
    {
        return $this->messages()
            ->latest()
            ->first();
    }
}