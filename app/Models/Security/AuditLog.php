<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'payload',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
