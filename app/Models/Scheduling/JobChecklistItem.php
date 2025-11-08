<?php

namespace App\Models\Scheduling;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_checklist_id',
        'label',
        'is_completed',
        'score',
        'notes',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'score' => 'float',
    ];

    public function checklist()
    {
        return $this->belongsTo(JobChecklist::class, 'job_checklist_id');
    }
}
