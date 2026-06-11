<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HospitalEventOption extends Model
{
    protected $table = 'hospital_event_options';

    protected $fillable = [
        'hospital_event_id',
        'sort_order',
        'name',
        'session_count',
        'normal_price',
        'event_price',
        'discount_rate',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'session_count' => 'integer',
        'normal_price' => 'integer',
        'event_price' => 'integer',
        'discount_rate' => 'float',
    ];

    protected $attributes = [
        'sort_order' => 0,
        'session_count' => 1,
        'normal_price' => 0,
        'event_price' => 0,
        'discount_rate' => 0,
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(HospitalEvent::class, 'hospital_event_id');
    }
}
