<?php

namespace App\Models\Scheduling;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'template_id',
        'score',
        'completed_at',
        'inspected_by',
        'feedback',
    ];

    protected $casts = [
        'score' => 'float',
        'completed_at' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function items()
    {
        return $this->hasMany(JobChecklistItem::class);
    }
}
