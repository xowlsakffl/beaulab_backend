<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Models;

use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HospitalEventDoctorAssignment extends Model
{
    protected $table = 'hospital_event_doctor_assignments';

    protected $fillable = [
        'hospital_event_id',
        'hospital_doctor_id',
        'sort_order',
        'is_career_visible',
        'is_activity_visible',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_career_visible' => 'boolean',
        'is_activity_visible' => 'boolean',
    ];

    protected $attributes = [
        'sort_order' => 0,
        'is_career_visible' => true,
        'is_activity_visible' => false,
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(HospitalEvent::class, 'hospital_event_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalDoctor::class, 'hospital_doctor_id');
    }
}
