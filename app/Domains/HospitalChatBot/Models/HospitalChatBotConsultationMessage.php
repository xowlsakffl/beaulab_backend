<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 상담 메시지는 Immutable Record로 취급한다. 저장 후 수정·삭제하지 않는다.
 */
final class HospitalChatBotConsultationMessage extends Model
{
    public const SENDER_HOSPITAL_USER = 'HOSPITAL_USER';

    public const SENDER_AGENT = 'AGENT';

    public const SENDER_SYSTEM = 'SYSTEM';

    public const TYPE_TEXT = 'TEXT';

    public const TYPE_SYSTEM_TEXT = 'SYSTEM_TEXT';

    /** PRD D-17. 변경 시 API Validation과 UI Counter도 함께 조정한다. */
    public const MAX_LENGTH = 2000;

    protected $table = 'hospital_chatbot_consultation_messages';

    public const UPDATED_AT = null;

    protected $fillable = [
        'consultation_id',
        'sequence',
        'sender_type',
        'sender_id',
        'sender_name_snapshot',
        'client_message_id',
        'message_type',
        'content',
        'broadcast_pending',
        'created_at',
    ];

    protected $casts = [
        'consultation_id' => 'integer',
        'sequence' => 'integer',
        'sender_id' => 'integer',
        'broadcast_pending' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(HospitalChatBotConsultation::class, 'consultation_id');
    }
}
