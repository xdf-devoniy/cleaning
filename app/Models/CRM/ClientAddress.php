<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'label',
        'line1',
        'line2',
        'city',
        'state',
        'postal_code',
        'latitude',
        'longitude',
        'is_default',
        'access_notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
