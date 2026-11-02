<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consultation extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected $appends = ['symptoms', 'private_notes', 'prescription_drugs'];

    public function getSymptomsAttribute(): ?string
    {
        return $this->complaints_symptoms;
    }

    public function getPrivateNotesAttribute(): ?string
    {
        return $this->private_doctor_notes;
    }

    public function getPrescriptionDrugsAttribute(): ?array
    {
        return $this->prescribed_drugs;
    }

    protected function casts(): array
    {
        return [
            'prescribed_drugs' => 'array',
            'requested_labs' => 'array',
            'selected_services' => 'array',
            'followup_date' => 'date',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
