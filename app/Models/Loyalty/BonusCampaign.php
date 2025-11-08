<?php

namespace App\Models\Loyalty;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'earn_multiplier',
        'channels',
        'rules',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'earn_multiplier' => 'float',
        'channels' => 'array',
        'rules' => 'array',
    ];
}
