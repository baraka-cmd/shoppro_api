<?php

namespace App\Models\Config;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'languages';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'native_name',
        'flag',
        'flag_emoji',
        'direction',
        'is_default',
        'is_active',
    ];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Accesseur : URL du drapeau
     * 
     * @return string
     */
    public function getFlagUrlAttribute(): string
    {
        if ($this->flag) {
            return asset('storage/' . $this->flag);
        }
        return asset('images/flags/' . $this->code . '.png');
    }

    /**
     * Accesseur : Nom complet avec drapeau
     * 
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        $emoji = $this->flag_emoji ? $this->flag_emoji . ' ' : '';
        return $emoji . $this->native_name . ' (' . $this->code . ')';
    }

    /**
     * Accesseur : La langue est-elle RTL ?
     * 
     * @return bool
     */
    public function getIsRtlAttribute(): bool
    {
        return $this->direction === 'rtl';
    }

    /**
     * Méthode : Définir comme langue par défaut
     * 
     * @return void
     */
    public function setAsDefault(): void
    {
        self::where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Méthode : Récupérer la langue par défaut
     * 
     * @return Language|null
     */
    public static function getDefault(): ?Language
    {
        return self::where('is_default', true)->first() 
               ?? self::where('is_active', true)->first();
    }

    /**
     * Scope : Langues actives
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Langues RTL
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRtl($query)
    {
        return $query->where('direction', 'rtl');
    }
}