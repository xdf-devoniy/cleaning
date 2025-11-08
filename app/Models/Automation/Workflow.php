<?php

namespace App\Models\Automation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'trigger',
        'actions',
        'is_active',
    ];

    protected $casts = [
        'trigger' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
    ];
}
