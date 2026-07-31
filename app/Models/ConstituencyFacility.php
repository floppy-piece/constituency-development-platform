<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConstituencyFacility extends Model
{
    use HasFactory;

    protected $primaryKey = 'facility_id';

    protected $fillable = [
        'mp_id',
        'facility_name',
        'facility_type',
        'location_name',
        'current_capacity',
        'current_enrollment',
        'avg_travel_distance_km',
        'capacity_deficit_percentage',
        'target_population_served',
        'is_in_cidp_plan',
        'cidp_priority_tier',
        'poverty_index_score',
    ];

    protected $casts = [
        'is_in_cidp_plan' => 'boolean',
        'poverty_index_score' => 'float',
    ];

    public function requests()
    {
        return $this->hasMany(ConstituencyRequest::class, 'facility_id', 'facility_id');
    }
}