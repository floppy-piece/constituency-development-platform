<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'phone_number',
        'whatsapp_id',
        'constituency_id',
        'whatsapp_linked_at',
    ];

    protected $casts = [
        'whatsapp_linked_at' => 'datetime',
    ];

    /**
     * Relationship: Citizen belongs to a Constituency.
     */
    public function constituency()
    {
        return $this->belongsTo(Constituency::class, 'constituency_id', 'constituency_id');
    }

    /**
     * Relationship: Citizen has submitted many development requests.
     */
    public function requests()
    {
        return $this->hasMany(ConstituencyRequest::class, 'user_id', 'user_id');
    }
}