<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'starts_at',
        'ends_at',
        'type',
        'status',
        'reason',
        'approved_by',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
