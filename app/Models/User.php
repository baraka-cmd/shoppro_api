<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User\UserProfile;
use App\Models\User\UserAddress;
use App\Models\User\Role;
use App\Models\Seller\SellerProfile;
use App\Models\Concerns\ScopesBySeller;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'users';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'email_verified_at',
        'last_login_at',
    ];

    /**
     * Attributs cachés dans les réponses JSON
     * 
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
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
        'email_verified_at',
        'last_login_at',
    ];

    // ============================================
    // RELATIONS EXISTANTES
    // ============================================

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
                    ->withTimestamps()
                    ->withPivot('assigned_at', 'assigned_by');
    }

    public function orders()
    {
        return $this->hasMany(\App\Models\Order\Order::class);
    }

    public function favorites()
    {
        return $this->hasMany(\App\Models\Review\Favorite::class);
    }

    public function reviews()
    {
        return $this->hasMany(\App\Models\Review\Review::class);
    }

    public function notifications()
    {
        return $this->hasMany(\App\Models\Support\Notification::class);
    }

    // ============================================
    // RELATIONS VENDEUR (NOUVELLES)
    // ============================================

    /**
     * Profil vendeur (1-to-1)
     */
    public function sellerProfile()
    {
        return $this->hasOne(SellerProfile::class);
    }

    /**
     * Produits dont l'utilisateur est vendeur
     */
    public function sellerProducts()
    {
        return $this->hasMany(\App\Models\Catalog\Product::class, 'seller_id');
    }

    /**
     * Items de commandes dont l'utilisateur est vendeur
     */
    public function sellerOrderItems()
    {
        return $this->hasMany(\App\Models\Order\OrderItem::class, 'seller_id');
    }

    /**
     * Avis reçus sur les produits de l'utilisateur
     */
    public function sellerReviews()
    {
        return $this->hasMany(\App\Models\Review\Review::class, 'seller_id');
    }

    /**
     * Stocks dont l'utilisateur est vendeur
     */
    public function sellerStocks()
    {
        return $this->hasMany(\App\Models\Catalog\Stock::class, 'seller_id');
    }

    /**
     * Promotions créées par l'utilisateur
     */
    public function sellerPromotions()
    {
        return $this->hasMany(\App\Models\Marketing\Promotion::class, 'seller_id');
    }

    /**
     * Transactions vendeur
     */
    public function sellerTransactions()
    {
        return $this->hasMany(\App\Models\Seller\SellerTransaction::class, 'seller_id');
    }

    /**
     * Payouts (retraits) vendeur
     */
    public function sellerPayouts()
    {
        return $this->hasMany(\App\Models\Seller\SellerPayout::class, 'seller_id');
    }

    // ============================================
    // MÉTHODES DE RÔLE
    // ============================================

    public function hasRole(string|array $roleName): bool
    {
        if (is_array($roleName)) {
            return $this->roles()->whereIn('name', $roleName)->exists();
        }

        return $this->roles()->where('name', $roleName)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isSeller(): bool
    {
        return $this->hasRole('seller');
    }

    public function isClient(): bool
    {
        return $this->hasRole('client');
    }

    /**
     * Vérifie si le vendeur est actif et KYC validé
     */
    public function isActiveSeller(): bool
    {
        if (!$this->isSeller()) {
            return false;
        }

        $profile = $this->sellerProfile;

        return $profile
            && $profile->is_active
            && $profile->kyc_status === 'verified';
    }

    // ============================================
    // ACCESSORS
    // ============================================

    public function getFullNameAttribute(): string
    {
        if ($this->profile && ($this->profile->first_name || $this->profile->last_name)) {
            return trim($this->profile->first_name . ' ' . $this->profile->last_name);
        }

        return $this->name;
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->profile && $this->profile->avatar) {
            return asset('storage/' . $this->profile->avatar);
        }

        return asset('images/default-avatar.png');
    }

    // ============================================
    // MÉTHODES DE GESTION
    // ============================================

    public function assignRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();

        if ($role) {
            $this->roles()->syncWithoutDetaching([$role->id]);
        }
    }

    public function removeRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();

        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    public function createAuthToken(string $tokenName = 'auth_token'): string
    {
        return $this->createToken($tokenName)->plainTextToken;
    }

    public function revokeAllTokens(): void
    {
        $this->tokens()->delete();
    }
}