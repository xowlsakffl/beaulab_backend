<?php

namespace App\Domains\HospitalEventAd\Models;

use Illuminate\Database\Eloquent\Model;

final class HospitalEventAdPricingVersion extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'prices' => 'array',
        'effective_at' => 'immutable_datetime',
        'cancelled_at' => 'immutable_datetime',
    ];
}
