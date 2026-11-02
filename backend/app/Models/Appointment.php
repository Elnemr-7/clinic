<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'actual_entered_at' => 'datetime',
            'actual_completed_at' => 'datetime',
            'vitals_data' => 'array',
            'consultation_fee' => 'decimal:2',
            'extra_services_total' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'is_paid' => 'boolean',
            'extra_services_paid' => 'boolean',
            'whatsapp_opted_in' => 'boolean',
            'near_turn_notified' => 'boolean',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(ClinicShift::class, 'shift_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }
}
