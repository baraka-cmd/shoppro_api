<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'payment_methods';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'icon',
        'logo',
        'transaction_fee',
        'fixed_fee',
        'minimum_amount',
        'maximum_amount',
        'countries',
        'currencies',
        'api_key',
        'api_secret',
        'webhook_url',
        'position',
        'is_active',
        'is_default',
        'is_test_mode',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'transaction_fee' => 'float',
        'fixed_fee' => 'float',
        'minimum_amount' => 'float',
        'maximum_amount' => 'float',
        'countries' => 'array',
        'currencies' => 'array',
        'position' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_test_mode' => 'boolean',
    ];

    /**
     * Attributs cachés (ne pas exposer les clés API)
     * 
     * @var array<int, string>
     */
    protected $hidden = [
        'api_key',
        'api_secret',
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
    ];

    /**
     * Relation : Paiements avec cette méthode
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'method', 'code');
    }

    /**
     * Accesseur : URL du logo
     * 
     * @return string
     */
    public function getLogoUrlAttribute(): string
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }
        return asset('images/default-payment.png');
    }

    /**
     * Accesseur : Frais totaux formatés
     * 
     * @return string
     */
    public function getFeesLabelAttribute(): string
    {
        $parts = [];
        
        if ($this->transaction_fee > 0) {
            $parts[] = $this->transaction_fee . '%';
        }
        if ($this->fixed_fee > 0) {
            $parts[] = number_format($this->fixed_fee, 2) . ' FCFA';
        }
        
        return $parts ? implode(' + ', $parts) : 'Gratuit';
    }

    /**
     * Méthode : Calculer les frais pour un montant
     * 
     * @param float $amount
     * @return float
     */
    public function calculateFees(float $amount): float
    {
        $fees = $this->fixed_fee;
        
        if ($this->transaction_fee > 0) {
            $fees += ($amount * $this->transaction_fee / 100);
        }
        
        return $fees;
    }

    /**
     * Méthode : Vérifier si le montant est dans les limites
     * 
     * @param float $amount
     * @return bool
     */
    public function isAmountValid(float $amount): bool
    {
        if ($this->minimum_amount && $amount < $this->minimum_amount) {
            return false;
        }
        
        if ($this->maximum_amount && $amount > $this->maximum_amount) {
            return false;
        }
        
        return true;
    }

    /**
     * Méthode : Vérifier si la devise est supportée
     * 
     * @param string $currency
     * @return bool
     */
    public function supportsCurrency(string $currency): bool
    {
        if (empty($this->currencies)) {
            return true;
        }
        
        return in_array($currency, $this->currencies);
    }

    /**
     * Scope : Méthodes actives
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Méthodes triées
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc');
    }
}