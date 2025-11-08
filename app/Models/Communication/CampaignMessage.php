<?php

namespace App\Models\Communication;

use App\Models\CRM\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'client_id',
        'channel',
        'status',
        'payload',
        'sent_at',
        'response_payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'response_payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
