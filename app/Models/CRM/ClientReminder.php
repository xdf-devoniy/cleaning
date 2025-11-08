<?php

namespace App\Models\CRM;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
        'remind_at',
        'channel',
        'message',
        'completed_at',
    ];

    protected $casts = [
        'remind_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
