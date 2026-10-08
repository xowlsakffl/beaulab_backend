<?php

namespace App\Domains\ConsultationPricing\Models;

use Illuminate\Database\Eloquent\Model;

final class ConsultationPricingVersion extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['ranges' => 'array', 'fixed_price' => 'integer', 'effective_at' => 'immutable_datetime', 'cancelled_at' => 'immutable_datetime'];
}
