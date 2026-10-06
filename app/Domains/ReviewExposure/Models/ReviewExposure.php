<?php

namespace App\Domains\ReviewExposure\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use App\Domains\HospitalReview\Models\HospitalReview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ReviewExposure extends Model
{
    use HasAuditLogs, HasOperationHistories, SoftDeletes;

    public const PLACEMENTS = ['SURGERY', 'PETIT'];

    public const LIMIT = 6;

    public const RELATIONS = ['review.hospital:id,name', 'creator:id,name'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = ['position_key', 'hospital_review_id', 'start_at', 'end_at', 'status'];

    protected $casts = ['start_at' => 'immutable_datetime', 'end_at' => 'immutable_datetime'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(HospitalReview::class, 'hospital_review_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'created_by_staff_id');
    }
}
