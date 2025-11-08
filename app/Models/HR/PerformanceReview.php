<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'reviewed_at',
        'score',
        'summary',
        'improvement_plan',
    ];

    protected $casts = [
        'reviewed_at' => 'date',
        'score' => 'float',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
