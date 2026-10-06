<?php

namespace App\Domains\BannerExposure\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\Notice\Models\Notice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class BannerExposure extends Model
{
    use HasAuditLogs, HasOperationHistories, SoftDeletes;

    public const PLACEMENTS = ['MAIN_POPUP', 'MAIN_VERTICAL_BANNER', 'MAIN_HORIZONTAL_BANNER', 'CONSULT_MEMO', 'SEARCH'];

    public const LIMIT = 6;

    public const RELATIONS = ['image', 'creator:id,name', 'hospital:id,name', 'event:id,name', 'notice:id,title', 'category:id,name'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = ['title', 'placement', 'connection_type', 'target_type', 'hospital_id', 'hospital_event_id', 'notice_id', 'category_id', 'external_url', 'open_mode', 'start_at', 'end_at', 'status'];

    protected $casts = ['start_at' => 'immutable_datetime', 'end_at' => 'immutable_datetime'];

    public function image(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')->where('collection', 'banner');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'created_by_staff_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(HospitalEvent::class, 'hospital_event_id');
    }

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
