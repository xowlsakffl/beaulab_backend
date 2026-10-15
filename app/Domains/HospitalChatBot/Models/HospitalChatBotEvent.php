<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 병의원 사용자의 챗봇 이용 이벤트. 열람과 질문 클릭 두 가지다.
 *
 * 상담 요청은 여기 넣지 않는다. 상담 테이블의 행이 그 사실이다. 같은 일을 두 곳에 적으면
 * 두 숫자가 어긋나기 시작한다. 통계를 집계할 때만 읽고, 수정되는 행은 없다.
 *
 * HasAuditLogs를 쓰지 않는다. 이벤트 한 건마다 감사 로그가 한 줄 더 생기면 쓰는 양이 두 배가 된다.
 */
final class HospitalChatBotEvent extends Model
{
    public const TYPE_WIDGET_OPENED = 'WIDGET_OPENED';

    public const TYPE_QNA_VIEWED = 'QNA_VIEWED';

    public const TYPES = [self::TYPE_WIDGET_OPENED, self::TYPE_QNA_VIEWED];

    public $timestamps = false;

    protected $table = 'hospital_chatbot_events';

    protected $fillable = [
        'event_type',
        'hospital_id',
        'account_hospital_id',
        'qna_id',
        'source_page_path',
        'occurred_at',
    ];

    protected $casts = [
        'hospital_id' => 'integer',
        'account_hospital_id' => 'integer',
        'qna_id' => 'integer',
        'occurred_at' => 'immutable_datetime',
    ];

    public function qna(): BelongsTo
    {
        // 삭제된 Q&A도 집계에는 나와야 한다. "삭제됨" 표시는 화면이 한다.
        return $this->belongsTo(HospitalChatBotQna::class, 'qna_id')->withTrashed();
    }
}
