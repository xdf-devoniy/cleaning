<?php

namespace App\Models\Scheduling;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'type',
        'path',
        'captured_at',
        'metadata',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
