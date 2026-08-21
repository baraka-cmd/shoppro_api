<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'settings';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'description',
        'is_public',
        'is_active',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Accesseur : Valeur typée selon le type
     * 
     * @return mixed
     */
    public function getTypedValueAttribute(): mixed
    {
        return match($this->type) {
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'boolean' => (bool) $this->value,
            'json' => json_decode($this->value, true),
            'array' => explode(',', $this->value),
            default => $this->value,
        };
    }

    /**
     * Accesseur : Groupe en français
     * 
     * @return string
     */
    public function getGroupLabelAttribute(): string
    {
        return match($this->group) {
            'general' => 'Général',
            'shipping' => 'Livraison',
            'payment' => 'Paiement',
            'notification' => 'Notifications',
            'seo' => 'SEO',
            'social' => 'Réseaux sociaux',
            default => $this->group ?? 'Autre',
        };
    }

    /**
     * Méthode : Récupérer une valeur par clé
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember('setting_' . $key, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->where('is_active', true)->first();
            return $setting ? $setting->typed_value : $default;
        });
    }

    /**
     * Méthode : Définir une valeur
     * 
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string|null $group
     * @return Setting
     */
    public static function set(string $key, mixed $value, string $type = 'string', ?string $group = null): Setting
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type' => $type,
                'group' => $group,
            ]
        );

        Cache::forget('setting_' . $key);
        return $setting;
    }

    /**
     * Méthode : Récupérer tous les paramètres groupés
     * 
     * @return array
     */
    public static function getAllGrouped(): array
    {
        return self::where('is_active', true)
                   ->get()
                   ->groupBy('group')
                   ->map(function ($group) {
                       return $group->mapWithKeys(function ($setting) {
                           return [$setting->key => $setting->typed_value];
                       });
                   })
                   ->toArray();
    }

    /**
     * Scope : Paramètres actifs
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Paramètres publics
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope : Paramètres par groupe
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}