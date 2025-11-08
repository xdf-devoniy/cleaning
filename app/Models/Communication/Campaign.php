<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'status',
        'segment_query',
        'channels',
        'scheduled_at',
        'payload',
        'sent_at',
    ];

    protected $casts = [
        'channels' => 'array',
        'payload' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(CampaignMessage::class);
    }
}
