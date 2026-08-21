<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RefreshToken extends Model
{
    /**
     * Table associée au modèle
     */
    protected $table = 'refresh_tokens';

    /**
     * Attributs assignables en masse
     */
    protected $fillable = [
        'user_id',
        'token',
        'device_name',
        'device_type',
        'ip_address',
        'user_agent',
        'expires_at',
        'last_used_at',
        'revoked_at',
        'is_revoked',
        'is_active',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_revoked' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relation : Le token appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Générer un refresh token unique
     */
    public static function generateToken(): string
    {
        return hash('sha256', Str::random(64) . microtime());
    }

    /**
     * Vérifier si le token est expiré
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Vérifier si le token est valide
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->is_revoked && !$this->isExpired();
    }

    /**
     * Révoquer le token
     */
    public function revoke(): void
    {
        $this->update([
            'is_revoked' => true,
            'is_active' => false,
            'revoked_at' => now(),
        ]);
    }

    /**
     * Mettre à jour la dernière utilisation
     */
    public function markAsUsed(): void
    {
        $this->update([
            'last_used_at' => now(),
        ]);
    }

    /**
     * Scope : Tokens actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('is_revoked', false);
    }

    /**
     * Scope : Tokens non expirés
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }
}