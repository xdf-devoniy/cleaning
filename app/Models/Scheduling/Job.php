<?php

namespace App\Models\Scheduling;

use App\Enums\JobStatus;
use App\Models\CRM\Client;
use App\Models\CRM\ClientAddress;
use App\Models\HR\Staff;
use App\Models\ServiceCatalog\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'address_id',
        'service_id',
        'status',
        'scheduled_at',
        'duration_minutes',
        'crew_id',
        'price',
        'notes',
        'recurrence_rule',
        'recurrence_ends_at',
        'start_coordinates',
        'end_coordinates',
    ];

    protected $casts = [
        'status' => JobStatus::class,
        'scheduled_at' => 'datetime',
        'duration_minutes' => 'integer',
        'price' => 'float',
        'recurrence_ends_at' => 'datetime',
        'start_coordinates' => 'array',
        'end_coordinates' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function address()
    {
        return $this->belongsTo(ClientAddress::class, 'address_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function crew()
    {
        return $this->belongsTo(Staff::class, 'crew_id');
    }

    public function checklist()
    {
        return $this->hasOne(JobChecklist::class);
    }

    public function photos()
    {
        return $this->hasMany(JobPhoto::class);
    }

    public function routePlan()
    {
        return $this->hasOne(JobRoutePlan::class);
    }
}
