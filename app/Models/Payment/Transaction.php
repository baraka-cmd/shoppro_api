<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Order\Order;

class Transaction extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'transactions';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_number',
        'user_id',
        'order_id',
        'amount',
        'fee',
        'net_amount',
        'currency',
        'type',
        'status',
        'payment_method',
        'payment_gateway',
        'gateway_transaction_id',
        'gateway_reference',
        'description',
        'metadata',
        'error_message',
        'completed_at',
        'failed_at',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'fee' => 'float',
        'net_amount' => 'float',
        'metadata' => 'array',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Dates à traiter comme des dates Carbon
     * 
     * @var array<string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'completed_at',
        'failed_at',
    ];

    /**
     * Relation : La transaction appartient à un utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : La transaction concerne une commande (optionnel)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
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
            'completed' => 'Complétée',
            'failed' => 'Échouée',
            'cancelled' => 'Annulée',
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
            'payment' => 'Paiement',
            'refund' => 'Remboursement',
            'wallet_topup' => 'Recharge portefeuille',
            'withdrawal' => 'Retrait',
            'transfer' => 'Transfert',
            'commission' => 'Commission',
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
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Méthode : Générer un numéro de transaction unique
     * 
     * @return string
     */
    public static function generateTransactionNumber(): string
    {
        $prefix = 'TRX';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -8));
        
        return $prefix . '-' . $date . '-' . $random;
    }

    /**
     * Méthode : Marquer comme complétée
     * 
     * @param string $gatewayTransactionId
     * @return void
     */
    public function markAsCompleted(string $gatewayTransactionId): void
    {
        $this->update([
            'status' => 'completed',
            'gateway_transaction_id' => $gatewayTransactionId,
            'completed_at' => now(),
        ]);
    }

    /**
     * Méthode : Marquer comme échouée
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
    }

    /**
     * Scope : Transactions par type
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
     * Scope : Transactions complétées
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}