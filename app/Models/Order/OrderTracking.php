<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;

class OrderTracking extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'order_trackings';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'tracking_number',
        'carrier',
        'carrier_url',
        'status',
        'current_location',
        'latitude',
        'longitude',
        'estimated_delivery',
        'actual_delivery',
        'recipient_name',
        'recipient_phone',
        'events',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'estimated_delivery' => 'datetime',
        'actual_delivery' => 'datetime',
        'events' => 'array',
    ];

    /**
     * Dates à traiter comme des dates Carbon
     * 
     * @var array<string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'estimated_delivery',
        'actual_delivery',
    ];

    /**
     * Relation : Le suivi appartient à une commande
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
            'picked_up' => 'Ramassé',
            'in_transit' => 'En transit',
            'out_for_delivery' => 'En cours de livraison',
            'delivered' => 'Livré',
            'failed' => 'Échec de livraison',
            'returned' => 'Retourné',
            default => $this->status,
        };
    }

    /**
     * Accesseur : URL de suivi complète
     * 
     * @return string|null
     */
    public function getTrackingUrlAttribute(): ?string
    {
        if ($this->carrier_url && $this->tracking_number) {
            return $this->carrier_url . $this->tracking_number;
        }
        return $this->carrier_url;
    }

    /**
     * Accesseur : La livraison est-elle en retard ?
     * 
     * @return bool
     */
    public function getIsDelayedAttribute(): bool
    {
        if (!$this->estimated_delivery || $this->status === 'delivered') {
            return false;
        }
        
        return now()->gt($this->estimated_delivery);
    }

    /**
     * Méthode : Ajouter un événement de suivi
     * 
     * @param string $status
     * @param string $location
     * @param string|null $note
     * @return void
     */
    public function addEvent(string $status, string $location, ?string $note = null): void
    {
        $events = $this->events ?? [];
        
        $events[] = [
            'status' => $status,
            'location' => $location,
            'note' => $note,
            'timestamp' => now()->toISOString(),
        ];
        
        $this->update([
            'status' => $status,
            'current_location' => $location,
            'events' => $events,
        ]);
        
        if ($status === 'delivered') {
            $this->update(['actual_delivery' => now()]);
        }
    }

    /**
     * Scope : Suivis par statut
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
     * Scope : Suivis par transporteur
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $carrier
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCarrier($query, string $carrier)
    {
        return $query->where('carrier', $carrier);
    }
}