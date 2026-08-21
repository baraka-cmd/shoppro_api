<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Order\Order;

class Payment extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'payments';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'payment_number',
        'order_id',
        'user_id',
        'amount',
        'fee',
        'total_amount',
        'currency',
        'method',
        'status',
        'transaction_id',
        'gateway_reference',
        'authorization_code',
        'response_data',
        'error_message',
        'paid_at',
        'failed_at',
        'refunded_at',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'fee' => 'float',
        'total_amount' => 'float',
        'response_data' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
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
        'paid_at',
        'failed_at',
        'refunded_at',
    ];

    /**
     * Relation : Le paiement appartient à une commande
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relation : Le paiement appartient à un utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Remboursements de ce paiement
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function refunds()
    {
        return $this->hasMany(Refund::class);
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
            'refunded' => 'Remboursé',
            'partially_refunded' => 'Partiellement remboursé',
            default => $this->status,
        };
    }

    /**
     * Accesseur : Méthode de paiement en français
     * 
     * @return string
     */
    public function getMethodLabelAttribute(): string
    {
        return match($this->method) {
            'card' => 'Carte bancaire',
            'paypal' => 'PayPal',
            'mobile_money' => 'Mobile Money',
            'bank_transfer' => 'Virement bancaire',
            'cash_on_delivery' => 'Paiement à la livraison',
            'wallet' => 'Portefeuille électronique',
            'points' => 'Points de fidélité',
            default => $this->method,
        };
    }

    /**
     * Accesseur : Montant formaté
     * 
     * @return string
     */
    public function getAmountFormattedAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Accesseur : Le paiement est-il remboursable ?
     * 
     * @return bool
     */
    public function getIsRefundableAttribute(): bool
    {
        return in_array($this->status, ['completed', 'partially_refunded']);
    }

    /**
     * Méthode : Générer un numéro de paiement unique
     * 
     * @return string
     */
    public static function generatePaymentNumber(): string
    {
        $prefix = 'PAY';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        
        return $prefix . '-' . $date . '-' . $random;
    }

    /**
     * Méthode : Marquer le paiement comme complété
     * 
     * @param string $transactionId
     * @param string|null $gatewayReference
     * @return void
     */
    public function markAsCompleted(string $transactionId, ?string $gatewayReference = null): void
    {
        $this->update([
            'status' => 'completed',
            'transaction_id' => $transactionId,
            'gateway_reference' => $gatewayReference,
            'paid_at' => now(),
        ]);
        
        $this->order->update([
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Méthode : Marquer le paiement comme échoué
     * 
     * @param string $errorMessage
     * @return void
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'failed_at' => now(),
        ]);
        
        $this->order->update([
            'payment_status' => 'failed',
        ]);
    }

    /**
     * Méthode : Marquer le paiement comme remboursé
     * 
     * @return void
     */
    public function markAsRefunded(): void
    {
        $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);
        
        $this->order->update([
            'payment_status' => 'refunded',
        ]);
    }

    /**
     * Scope : Paiements par statut
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
     * Scope : Paiements complétés
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}