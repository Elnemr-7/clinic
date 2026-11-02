<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PrescriptionTemplate extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected $appends = ['name', 'diagnosis', 'drugs_payload'];

    public function getNameAttribute(): ?string
    {
        return $this->template_name;
    }

    public function getDiagnosisAttribute(): ?string
    {
        return $this->diagnosis_default;
    }

    public function getDrugsPayloadAttribute(): ?array
    {
        return $this->drugs_json;
    }

    protected function casts(): array
    {
        return [
            'drugs_json' => 'array',
        ];
    }
}
