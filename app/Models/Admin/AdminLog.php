<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class AdminLog extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'admin_logs';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'target_type',
        'target_id',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'is_successful',
        'error_message',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'is_successful' => 'boolean',
    ];

    /**
     * Relation : Le log appartient à un utilisateur (admin)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accesseur : Module en français
     * 
     * @return string
     */
    public function getModuleLabelAttribute(): string
    {
        return match($this->module) {
            'products' => 'Produits',
            'orders' => 'Commandes',
            'users' => 'Utilisateurs',
            'categories' => 'Catégories',
            'payments' => 'Paiements',
            'settings' => 'Paramètres',
            default => $this->module ?? 'Général',
        };
    }

    /**
     * Accesseur : Différence entre old_data et new_data
     * 
     * @return array
     */
    public function getChangesAttribute(): array
    {
        if (!$this->old_data || !$this->new_data) {
            return [];
        }

        $changes = [];
        foreach ($this->new_data as $key => $value) {
            if (!array_key_exists($key, $this->old_data)) {
                $changes[$key] = ['old' => null, 'new' => $value];
            } elseif ($this->old_data[$key] !== $value) {
                $changes[$key] = ['old' => $this->old_data[$key], 'new' => $value];
            }
        }

        return $changes;
    }

    /**
     * Méthode : Créer un log d'action
     * 
     * @param User $user
     * @param string $action
     * @param string $module
     * @param string|null $description
     * @param array|null $oldData
     * @param array|null $newData
     * @return AdminLog
     */
    public static function log(
        User $user,
        string $action,
        string $module,
        ?string $description = null,
        ?array $oldData = null,
        ?array $newData = null
    ): AdminLog {
        return self::create([
            'user_id' => $user->id,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'is_successful' => true,
        ]);
    }

    /**
     * Scope : Logs par module
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $module
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope : Logs par action
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $action
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope : Logs réussis
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('is_successful', true);
    }

    /**
     * Scope : Logs échoués
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('is_successful', false);
    }
}