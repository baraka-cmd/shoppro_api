<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Order\Order;

class Refund extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'refunds';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'refund_number',
        'payment_id',
        'order_id',
        'user_id',
        'amount',
        'fee_refunded',
        'total_refunded',
        'type',
        'status',
        'reason',
        'note',
        'gateway_refund_id',
        'processed_at',
        'completed_at',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'fee_refunded' => 'float',
        'total_refunded' => 'float',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Dates à traiter comme des dates Carbon
     * 
     * @var array<string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'processed_at',
        'completed_at',
    ];

    /**
     * Relation : Le remboursement appartient à un paiement
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Relation : Le remboursement concerne une commande
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relation : Le remboursement concerne un utilisateur
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
            'pending' => 'En attente',
            'processing' => 'En cours',
            'completed' => 'Complété',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            default => $this->status,
        };
    }

    /**
     * Accesseur : Type en français
     * 
     * @return string
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'full' => 'Remboursement total',
            'partial' => 'Remboursement partiel',
            'shipping_only' => 'Frais de livraison uniquement',
            default => $this->type,
        };
    }

    /**
     * Accesseur : Montant formaté
     * 
     * @return string
     */
    public function getAmountFormattedAttribute(): string
    {
        return number_format($this->amount, 2) . ' FCFA';
    }

    /**
     * Méthode : Générer un numéro de remboursement unique
     * 
     * @return string
     */
    public static function generateRefundNumber(): string
    {
        $prefix = 'RMB';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        
        return $prefix . '-' . $date . '-' . $random;
    }

    /**
     * Méthode : Traiter le remboursement
     * 
     * @param string $gatewayRefundId
     * @return void
     */
    public function process(string $gatewayRefundId): void
    {
        $this->update([
            'status' => 'processing',
            'gateway_refund_id' => $gatewayRefundId,
            'processed_at' => now(),
        ]);
    }

    /**
     * Méthode : Marquer comme complété
     * 
     * @return void
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        
        // Mettre à jour le paiement
        $this->payment->markAsRefunded();
    }

    /**
     * Méthode : Marquer comme échoué
     * 
     * @param string $errorMessage
     * @return void
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'note' => $errorMessage,
        ]);
    }

    /**
     * Scope : Remboursements par statut
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope : Remboursements complétés
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}