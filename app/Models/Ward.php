<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;

    protected $table = 'wards';
    protected $primaryKey = 'ward_id';

    protected $fillable = [
        'constituency_id',
        'name',
        'latitude',
        'longitude',
        'approximate_size',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'approximate_size' => 'float',
    ];

    public function constituency()
    {
        return $this->belongsTo(Constituency::class, 'constituency_id', 'constituency_id');
    }
}