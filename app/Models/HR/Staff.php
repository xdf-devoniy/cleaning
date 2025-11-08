<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'role',
        'team',
        'hourly_rate',
        'commission_rate',
        'employment_type',
        'hired_at',
        'terminated_at',
        'avatar_path',
        'notes',
    ];

    protected $casts = [
        'hourly_rate' => 'float',
        'commission_rate' => 'float',
        'hired_at' => 'datetime',
        'terminated_at' => 'datetime',
    ];

    public function attendances()
    {
        return $this->hasMany(TimeAttendance::class);
    }

    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function trainingRecords()
    {
        return $this->hasMany(TrainingRecord::class);
    }

    public function performanceReviews()
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function loans()
    {
        return $this->hasMany(StaffLoan::class);
    }
}
