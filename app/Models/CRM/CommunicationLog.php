<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'channel',
        'subject',
        'payload',
        'occurred_at',
        'direction',
        'external_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
