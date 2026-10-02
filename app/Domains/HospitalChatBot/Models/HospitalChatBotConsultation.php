<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class HospitalChatBotConsultation extends Model
{
    use HasAuditLogs;

    protected $table = 'hospital_chatbot_consultations';

    protected $fillable = [
        'hospital_id',
        'hospital_name_snapshot',
        'requested_by_account_hospital_id',
        'requester_name_snapshot',
        'context_qna_id',
        'context_qna_question_snapshot',
        'context_qna_answer_snapshot',
        'status',
        'active_hospital_id',
        'assigned_staff_id',
        'assigned_staff_name_snapshot',
        'requested_at',
        'connected_at',
        'ended_at',
        'canceled_at',
        'close_reason',
        'last_message_at',
        'last_message_sequence',
        'source_application',
        'source_page_path',
        'version',
    ];

    protected $casts = [
        'hospital_id' => 'integer',
        'requested_by_account_hospital_id' => 'integer',
        'context_qna_id' => 'integer',
        'active_hospital_id' => 'integer',
        'assigned_staff_id' => 'integer',
        'requested_at' => 'datetime',
        'connected_at' => 'datetime',
        'ended_at' => 'datetime',
        'canceled_at' => 'datetime',
        'last_message_at' => 'datetime',
        'last_message_sequence' => 'integer',
        'version' => 'integer',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(AccountHospital::class, 'requested_by_account_hospital_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'assigned_staff_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(HospitalChatBotConsultationMessage::class, 'consultation_id');
    }

    public function latestMessage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(HospitalChatBotConsultationMessage::class, 'consultation_id')->latestOfMany('sequence');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(HospitalChatBotConsultationStatusHistory::class, 'consultation_id');
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', HospitalChatBotConsultationStatus::active());
    }

    public function isActive(): bool
    {
        return HospitalChatBotConsultationStatus::isActive((string) $this->status);
    }
}
