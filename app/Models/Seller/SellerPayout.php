<?php

namespace App\Models\Seller;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * A seller withdrawal request and its processing lifecycle.
 */
class SellerPayout extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'seller_payouts';

    protected $fillable = [
        'reference', 'seller_id', 'amount', 'fee', 'net_amount', 'currency',
        'method', 'account', 'account_name', 'status', 'note', 'admin_note',
        'failure_reason', 'external_reference', 'external_provider',
        'requested_at', 'processed_at', 'completed_at', 'failed_at',
        'cancelled_at', 'processed_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'fee' => 'float',
        'net_amount' => 'float',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function transactions()
    {
        return $this->hasMany(SellerTransaction::class, 'payout_id');
    }
}
