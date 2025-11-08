<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_id',
        'staff_id',
        'base_pay',
        'overtime_pay',
        'bonus',
        'deductions',
        'net_pay',
        'meta',
    ];

    protected $casts = [
        'base_pay' => 'float',
        'overtime_pay' => 'float',
        'bonus' => 'float',
        'deductions' => 'float',
        'net_pay' => 'float',
        'meta' => 'array',
    ];

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
