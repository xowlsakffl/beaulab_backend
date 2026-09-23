<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\HospitalChatBot;

use App\Domains\HospitalChatBot\Dto\Hospital\HospitalChatBotQnaForHospitalDto;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotQnaForStaffDto;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use PHPUnit\Framework\TestCase;

final class HospitalChatBotQnaDtoTest extends TestCase
{
    private string $previousTimezone = 'UTC';

    /**
     * 이 테스트는 Laravel 앱을 띄우지 않아 PHP 기본 시간대를 그대로 쓴다.
     * 실행 순서나 개발자 PC 설정에 따라 ISO 변환 결과가 달라지므로 UTC로 고정한다.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->previousTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->previousTimezone);
        parent::tearDown();
    }

    public function test_hospital_payload_only_exposes_active_content_fields(): void
    {
        $payload = HospitalChatBotQnaForHospitalDto::fromModel($this->qna());

        self::assertSame([
            'id' => 7,
            'category' => '병원회원',
            'question' => '비밀번호 변경은 어떻게 하나요?',
            'answer' => "계정 메뉴에서 변경해 주세요.\nHTML은 실행하지 않습니다.",
            'sort_order' => 3,
        ], $payload);
        self::assertArrayNotHasKey('status', $payload);
        self::assertArrayNotHasKey('created_by_staff_id', $payload);
    }

    public function test_staff_payload_includes_management_fields(): void
    {
        $payload = HospitalChatBotQnaForStaffDto::fromModel($this->qna());

        self::assertSame(HospitalChatBotQna::STATUS_ACTIVE, $payload['status']);
        self::assertSame(11, $payload['created_by_staff_id']);
        self::assertSame(12, $payload['updated_by_staff_id']);
        self::assertSame('2026-09-23T01:02:03.000000Z', $payload['created_at']);
        self::assertSame('2026-09-23T04:05:06.000000Z', $payload['updated_at']);
    }

    private function qna(): HospitalChatBotQna
    {
        $qna = new HospitalChatBotQna;
        $qna->setDateFormat('Y-m-d H:i:s');
        $qna->setRawAttributes([
            'id' => 7,
            'category' => '병원회원',
            'question' => '비밀번호 변경은 어떻게 하나요?',
            'answer' => "계정 메뉴에서 변경해 주세요.\nHTML은 실행하지 않습니다.",
            'status' => HospitalChatBotQna::STATUS_ACTIVE,
            'sort_order' => 3,
            'created_by_staff_id' => 11,
            'updated_by_staff_id' => 12,
            'created_at' => '2026-09-23 01:02:03',
            'updated_at' => '2026-09-23 04:05:06',
        ]);

        return $qna;
    }
}
