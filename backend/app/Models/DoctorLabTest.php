<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class DoctorLabTest extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];
}
