<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Role extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'roles';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'level',
        'color',
        'icon',
        'is_active',
        'is_system',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
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
     * Relation : Les utilisateurs ayant ce rôle
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user')
                    ->withTimestamps()
                    ->withPivot('assigned_at', 'assigned_by');
    }

    /**
     * Relation : Les permissions associées à ce rôle
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
                    ->withTimestamps();
    }

    /**
     * Méthode : Vérifier si le rôle a une permission
     * 
     * @param string $permissionName
     * @return bool
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()
                    ->where('name', $permissionName)
                    ->exists();
    }

    /**
     * Méthode : Vérifier si le rôle a plusieurs permissions
     * 
     * @param array $permissionNames
     * @return bool
     */
    public function hasAnyPermission(array $permissionNames): bool
    {
        return $this->permissions()
                    ->whereIn('name', $permissionNames)
                    ->exists();
    }

    /**
     * Méthode : Vérifier si le rôle a toutes les permissions
     * 
     * @param array $permissionNames
     * @return bool
     */
    public function hasAllPermissions(array $permissionNames): bool
    {
        $rolePermissions = $this->permissions()->pluck('name')->toArray();
        return count(array_intersect($permissionNames, $rolePermissions)) === count($permissionNames);
    }

    /**
     * Méthode : Assigner une permission au rôle
     * 
     * @param string|array $permissionNames
     * @return void
     */
    public function givePermissionTo(string|array $permissionNames): void
    {
        $permissions = Permission::whereIn('name', (array) $permissionNames)->get();
        $this->permissions()->syncWithoutDetaching($permissions->pluck('id')->toArray());
    }

    /**
     * Méthode : Retirer une permission du rôle
     * 
     * @param string|array $permissionNames
     * @return void
     */
    public function revokePermissionTo(string|array $permissionNames): void
    {
        $permissions = Permission::whereIn('name', (array) $permissionNames)->get();
        $this->permissions()->detach($permissions->pluck('id')->toArray());
    }

    /**
     * Scope : Rôles actifs uniquement
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Rôles système (non supprimables)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
}