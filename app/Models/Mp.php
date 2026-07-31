<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class Mp extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'mps';
    protected $primaryKey = 'mp_id';

    protected $fillable = [
        'mp_name',
        'constituency_name',
        'email',
        'email_verified_at',
        'password',
        'term_start',
        'term_end',
        'avatar_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'term_start' => 'datetime',
        'term_end' => 'datetime',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role' => 'mp',
            'constituency' => $this->constituency_name,
        ];
    }

    /**
     * Relationship: An MP has many constituent requests.
     */
    public function requests()
    {
        return $this->hasMany(ConstituencyRequest::class, 'mp_id', 'mp_id');
    }
}