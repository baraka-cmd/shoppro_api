<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use App\Models\User\Role;

class Permission extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'permissions';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'group',
        'action',
        'is_active',
        'is_system',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Relation : Les rôles ayant cette permission
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_role')
                    ->withTimestamps();
    }

    /**
     * Accesseur : Groupe en français
     * 
     * @return string
     */
    public function getGroupLabelAttribute(): string
    {
        return match($this->group) {
            'products' => 'Produits',
            'orders' => 'Commandes',
            'users' => 'Utilisateurs',
            'categories' => 'Catégories',
            'payments' => 'Paiements',
            'reviews' => 'Avis',
            'settings' => 'Paramètres',
            default => $this->group ?? 'Autre',
        };
    }

    /**
     * Accesseur : Action en français
     * 
     * @return string
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'create' => 'Créer',
            'read' => 'Lire',
            'update' => 'Modifier',
            'delete' => 'Supprimer',
            'manage' => 'Gérer',
            default => $this->action ?? 'Autre',
        };
    }

    /**
     * Scope : Permissions par groupe
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope : Permissions actives uniquement
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Méthode : Récupérer toutes les permissions groupées
     * 
     * @return array
     */
    public static function getGroupedPermissions(): array
    {
        return self::active()
                   ->get()
                   ->groupBy('group')
                   ->toArray();
    }
}