<?php

namespace App\Domains\EventExposure\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EventExposure extends Model
{
    use HasAuditLogs, HasOperationHistories, SoftDeletes;

    public const PLACEMENTS = ['SURGERY_TOP_BANNER', 'SURGERY_HOT_EVENT', 'SURGERY_CATEGORY_BANNER', 'PETIT_TOP_BANNER', 'PETIT_HOT_EVENT', 'PETIT_CATEGORY_BANNER'];

    public const LIMIT = 6;

    public const RELATIONS = ['event.thumbnailImage', 'event.hospital:id,name', 'creator:id,name'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = ['position_key', 'hospital_event_id', 'start_at', 'end_at', 'status'];

    protected $casts = ['start_at' => 'immutable_datetime', 'end_at' => 'immutable_datetime'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(HospitalEvent::class, 'hospital_event_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'created_by_staff_id');
    }
}
