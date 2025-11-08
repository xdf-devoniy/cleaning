<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'checked_in_at',
        'checked_out_at',
        'status',
        'notes',
        'geo_coordinates',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'geo_coordinates' => 'array',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
