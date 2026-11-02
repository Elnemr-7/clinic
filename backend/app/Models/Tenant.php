<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'subscription_ends_at' => 'datetime',
            'whatsapp_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'is_custom_standalone_website' => 'boolean',
            'website_settings' => 'array',
            'vitals_config' => 'array',
            'print_settings' => 'array',
            'notification_settings' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clinics(): HasMany
    {
        return $this->hasMany(Clinic::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function drugs(): HasMany
    {
        return $this->hasMany(DoctorDrug::class);
    }

    public function prescriptionTemplates(): HasMany
    {
        return $this->hasMany(PrescriptionTemplate::class);
    }

    public function labTests(): HasMany
    {
        return $this->hasMany(DoctorLabTest::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ClinicService::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public function walletTopups(): HasMany
    {
        return $this->hasMany(WalletTopup::class);
    }
}
