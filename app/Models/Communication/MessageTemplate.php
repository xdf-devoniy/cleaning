<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'channel',
        'subject',
        'body',
        'variables',
        'locale',
    ];

    protected $casts = [
        'variables' => 'array',
    ];
}
