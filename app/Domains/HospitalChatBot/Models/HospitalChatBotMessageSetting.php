<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Models;

use App\Common\Concerns\HasAuditLogs;
use Illuminate\Database\Eloquent\Model;

final class HospitalChatBotMessageSetting extends Model
{
    use HasAuditLogs;

    public const WELCOME = '안녕하세요. 궁금한 항목을 선택하면 바로 안내해 드릴게요.';

    public const CONNECTION = '안녕하십니까. 뷰랩 상담사 {상담원명}입니다.';

    public $incrementing = false;

    protected $table = 'hospital_chatbot_message_settings';

    protected $fillable = ['id', 'welcome_message', 'connection_message', 'macros', 'updated_by_staff_id'];

    protected $casts = ['macros' => 'array'];

    public static function defaults(): array
    {
        return [
            'welcome_message' => self::WELCOME,
            'connection_message' => self::CONNECTION,
            'macros' => [
                ['shortcut' => '확인', 'content' => '문의하신 내용을 확인하고 있습니다. 잠시만 기다려 주세요.'],
                ['shortcut' => '추가정보', 'content' => '정확한 확인을 위해 문제가 발생한 화면과 내용을 자세히 알려주시겠습니까?'],
                ['shortcut' => '완료', 'content' => '문의하신 사항을 처리했습니다. 다시 확인해 주시겠습니까?'],
                ['shortcut' => '마무리', 'content' => '추가로 궁금하신 사항이 있으시면 말씀해 주세요. 감사합니다.'],
            ],
        ];
    }
}
