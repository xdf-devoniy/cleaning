<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'documentable_id',
        'documentable_type',
        'path',
        'name',
        'category',
        'metadata',
        'uploaded_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }
}
