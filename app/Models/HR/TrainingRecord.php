<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'course_name',
        'provider',
        'completed_at',
        'certificate_path',
        'expires_at',
    ];

    protected $casts = [
        'completed_at' => 'date',
        'expires_at' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
