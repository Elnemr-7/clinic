<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTopup extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount_egp' => 'decimal:2',
        ];
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'approved_by_admin_id');
    }
}
