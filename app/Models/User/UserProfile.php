<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class UserProfile extends Model
{
    use SoftDeletes;

    /**
     * Table associée au modèle
     * 
     * @var string
     */
    protected $table = 'user_profiles';

    /**
     * Attributs assignables en masse
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'avatar',
        'birth_date',
        'gender',
        'country',
        'city',
        'address',
        'language',
        'currency',
        'newsletter_subscribed',
        'sms_notifications',
        'push_notifications',
        'total_orders',
        'total_spent',
    ];

    /**
     * Attributs cachés dans les réponses JSON
     * 
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Attributs à caster
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'newsletter_subscribed' => 'boolean',
        'sms_notifications' => 'boolean',
        'push_notifications' => 'boolean',
        'total_orders' => 'integer',
        'total_spent' => 'float',
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
        'birth_date',
    ];

    /**
     * Relation : Le profil appartient à un utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accesseur : Nom complet
     * 
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Accesseur : URL de l'avatar
     * 
     * @return string
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return asset('images/default-avatar.png');
    }

    /**
     * Scope : Profils avec newsletter activée
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNewsletterSubscribed($query)
    {
        return $query->where('newsletter_subscribed', true);
    }

    /**
     * Scope : Profils par ville
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $city
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }
}