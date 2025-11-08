<?php

namespace App\Models\CRM;

use App\Enums\LeadStatus;
use App\Models\Finance\Invoice;
use App\Models\Finance\Quote;
use App\Models\HR\Staff;
use App\Models\Loyalty\LoyaltyAccount;
use App\Models\Scheduling\Job;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'lead_status',
        'source',
        'tags',
        'preferred_language',
        'notes',
        'last_contacted_at',
        'assigned_to_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'lead_status' => LeadStatus::class,
        'last_contacted_at' => 'datetime',
    ];

    public function contacts()
    {
        return $this->hasMany(ClientContact::class);
    }

    public function addresses()
    {
        return $this->hasMany(ClientAddress::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function notes()
    {
        return $this->hasMany(ClientNote::class);
    }

    public function reminders()
    {
        return $this->hasMany(ClientReminder::class);
    }

    public function communicationLogs()
    {
        return $this->hasMany(CommunicationLog::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function loyaltyAccount()
    {
        return $this->hasOne(LoyaltyAccount::class);
    }

    public function accountManager()
    {
        return $this->belongsTo(Staff::class, 'assigned_to_id');
    }

    public function scopeTagged($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function getLifetimeValueAttribute(): float
    {
        return (float) $this->invoices()->paid()->sum('total');
    }

    public function getAccountsReceivableBalanceAttribute(): float
    {
        return (float) $this->invoices()->whereIn('status', ['sent', 'partial', 'overdue'])->sum('balance_due');
    }

    public function getLastActivityAtAttribute(): ?Carbon
    {
        return $this->communicationLogs()->latest('occurred_at')->value('occurred_at')
            ?? $this->jobs()->latest('scheduled_at')->value('scheduled_at');
    }
}
