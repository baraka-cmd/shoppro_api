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
        'is_active',        // ✅ AJOUTÉ (utilisé dans AdminUserController)
        'email_verified_at', // ✅ AJOUTÉ (utilisé pour vérification email)
        'last_login_at'
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
    ];

    /**
     * Relation : Profil de l'utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Relation : Adresses de l'utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    /**
     * Relation : Rôles de l'utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
                    ->withTimestamps()
                    ->withPivot('assigned_at', 'assigned_by');
    }

    /**
     * Relation : Commandes de l'utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(\App\Models\Order\Order::class);
    }

    /**
     * Relation : Favoris de l'utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function favorites()
    {
        return $this->hasMany(\App\Models\Review\Favorite::class);
    }

    /**
     * Relation : Avis de l'utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews()
    {
        return $this->hasMany(\App\Models\Review\Review::class);
    }

    /**
     * Relation : Notifications de l'utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany(\App\Models\Support\Notification::class);
    }

    /**
     * Vérifier si l'utilisateur a un rôle
     * 
     * @param string|array $roleName
     * @return bool
     */
    public function hasRole(string|array $roleName): bool
    {
        if (is_array($roleName)) {
            return $this->roles()->whereIn('name', $roleName)->exists();
        }
        
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Vérifier si l'utilisateur est admin
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Vérifier si l'utilisateur est vendeur
     * 
     * @return bool
     */
    public function isSeller(): bool
    {
        return $this->hasRole('seller');
    }

    /**
     * Vérifier si l'utilisateur est client
     * 
     * @return bool
     */
    public function isClient(): bool
    {
        return $this->hasRole('client');
    }

    /**
     * Accesseur : Nom complet (avec profil si disponible)
     * 
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        if ($this->profile && ($this->profile->first_name || $this->profile->last_name)) {
            return trim($this->profile->first_name . ' ' . $this->profile->last_name);
        }
        
        return $this->name;
    }

    /**
     * Accesseur : URL de l'avatar
     * 
     * @return string
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->profile && $this->profile->avatar) {
            return asset('storage/' . $this->profile->avatar);
        }
        
        return asset('images/default-avatar.png');
    }

    /**
     * Méthode : Assigner un rôle
     * 
     * @param string $roleName
     * @return void
     */
    public function assignRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        
        if ($role) {
            $this->roles()->syncWithoutDetaching([$role->id]);
        }
    }

    /**
     * Méthode : Retirer un rôle
     * 
     * @param string $roleName
     * @return void
     */
    public function removeRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        
        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * Méthode : Créer un token d'authentification
     * 
     * @param string $tokenName
     * @return string
     */
    public function createAuthToken(string $tokenName = 'auth_token'): string
    {
        return $this->createToken($tokenName)->plainTextToken;
    }

    /**
     * Méthode : Révoquer tous les tokens
     * 
     * @return void
     */
    public function revokeAllTokens(): void
    {
        $this->tokens()->delete();
    }
}