<?php

namespace App\Models\Scheduling;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobRoutePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'provider',
        'distance_km',
        'duration_minutes',
        'route_payload',
        'eta_url',
    ];

    protected $casts = [
        'distance_km' => 'float',
        'duration_minutes' => 'integer',
        'route_payload' => 'array',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
