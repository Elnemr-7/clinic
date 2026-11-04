<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected $appends = [
        'name',
        'address',
        'city',
        'examination_fee',
        'followup_fee',
    ];

    public function getNameAttribute(): ?string
    {
        return $this->name_ar;
    }

    public function getAddressAttribute(): ?string
    {
        return $this->address_ar;
    }

    public function getCityAttribute(): ?string
    {
        return $this->city_ar;
    }

    public function getExaminationFeeAttribute(): float
    {
        return (float) ($this->consultation_price ?? 0);
    }

    public function getFollowupFeeAttribute(): float
    {
        return (float) ($this->followup_price ?? 0);
    }

    protected function casts(): array
    {
        return [
            'consultation_price' => 'decimal:2',
            'followup_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(ClinicShift::class);
    }

    public function daysOff(): HasMany
    {
        return $this->hasMany(ClinicDayOff::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
