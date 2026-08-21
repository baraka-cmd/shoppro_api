<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\User\UserAddress;

class Order extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'orders';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'order_number',
        'user_id',
        'shipping_address_id',
        'billing_address_id',
        'subtotal',
        'discount',
        'coupon_discount',
        'shipping_fee',
        'tax',
        'total',
        'status',
        'payment_status',
        'shipping_method',
        'tracking_number',
        'estimated_delivery_date',
        'shipped_at',
        'delivered_at',
        'coupon_code',
        'customer_note',
        'admin_note',
        'currency',
        'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'float',
        'discount' => 'float',
        'coupon_discount' => 'float',
        'shipping_fee' => 'float',
        'tax' => 'float',
        'total' => 'float',
        'estimated_delivery_date' => 'date',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
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
        'estimated_delivery_date',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    /**
     * Constantes de statut
     */
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_RETURNED = 'returned';

    /**
     * Relation : La commande appartient à un utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Adresse de livraison
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shippingAddress()
    {
        return $this->belongsTo(UserAddress::class, 'shipping_address_id');
    }

    /**
     * Relation : Adresse de facturation
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function billingAddress()
    {
        return $this->belongsTo(UserAddress::class, 'billing_address_id');
    }

    /**
     * Relation : Éléments de la commande
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relation : Historique des statuts
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * Relation : Suivi de la commande
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function trackings()
    {
        return $this->hasMany(OrderTracking::class);
    }

    /**
     * Relation : Paiements de la commande
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments()
    {
        return $this->hasMany(\App\Models\Payment\Payment::class);
    }

    /**
     * Accesseur : Statut en français
     * 
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_CONFIRMED => 'Confirmée',
            self::STATUS_PROCESSING => 'En préparation',
            self::STATUS_SHIPPED => 'Expédiée',
            self::STATUS_DELIVERED => 'Livrée',
            self::STATUS_CANCELLED => 'Annulée',
            self::STATUS_REFUNDED => 'Remboursée',
            self::STATUS_RETURNED => 'Retournée',
            default => $this->status,
        };
    }

    /**
     * Accesseur : Statut de paiement en français
     * 
     * @return string
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'En attente',
            'paid' => 'Payée',
            'failed' => 'Échouée',
            'refunded' => 'Remboursée',
            'partially_refunded' => 'Partiellement remboursée',
            default => $this->payment_status,
        };
    }

    /**
     * Accesseur : Total formaté
     * 
     * @return string
     */
    public function getTotalFormattedAttribute(): string
    {
        return number_format($this->total, 2) . ' ' . $this->currency;
    }

    /**
     * Scope : Commandes par statut
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
     * Scope : Commandes en cours
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_CANCELLED,
            self::STATUS_DELIVERED,
            self::STATUS_RETURNED,
        ]);
    }

    /**
     * Méthode : Générer un numéro de commande unique
     * 
     * @return string
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'CMD';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        
        return $prefix . '-' . $date . '-' . $random;
    }

    /**
     * Méthode : Changer le statut de la commande
     * 
     * @param string $newStatus
     * @param string|null $note
     * @return void
     */
    public function updateStatus(string $newStatus, ?string $note = null): void
    {
        $oldStatus = $this->status;
        
        $this->update(['status' => $newStatus]);
        
        $this->statusHistory()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $note,
        ]);
        
        if ($newStatus === self::STATUS_SHIPPED) {
            $this->update(['shipped_at' => now()]);
        }
        
        if ($newStatus === self::STATUS_DELIVERED) {
            $this->update(['delivered_at' => now()]);
        }
        
        if ($newStatus === self::STATUS_CANCELLED) {
            $this->update(['cancelled_at' => now()]);
        }
    }

    /**
     * Méthode : Annuler la commande
     * 
     * @param string $reason
     * @return bool
     */
    public function cancel(string $reason): bool
    {
        if (in_array($this->status, [
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ])) {
            return false;
        }
        
        $this->updateStatus(self::STATUS_CANCELLED, 'Annulation : ' . $reason);
        $this->update(['cancellation_reason' => $reason]);
        
        return true;
    }
}