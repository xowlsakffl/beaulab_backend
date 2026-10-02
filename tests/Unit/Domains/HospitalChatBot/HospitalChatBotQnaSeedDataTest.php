<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\HospitalChatBot;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use JsonException;
use PHPUnit\Framework\TestCase;

final class HospitalChatBotQnaSeedDataTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function test_seed_data_contains_all_reviewed_beaulab_qnas_without_duplicates(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 4).'/database/seeders/data/hospital_chatbot_qnas.json');
        self::assertNotFalse($contents);

        $items = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        self::assertNotEmpty($items);
        self::assertSame(range(1, count($items)), array_column($items, 'sort_order'));

        $keys = [];
        $categoryCounts = [];
        $allowedSheets = [
            '병의원회원',
            '병의원회원 (바비톡으로 바꾸기)',
            '의료진 프로필',
            '상담신청',
            '이벤트 등록',
            '충전금',
            '게시물',
        ];

        foreach ($items as $item) {
            self::assertNotSame('', trim((string) ($item['category'] ?? '')));
            self::assertNotSame('', trim((string) ($item['question'] ?? '')));
            self::assertNotSame('', trim((string) ($item['answer'] ?? '')));
            self::assertContains($item['source_sheet'] ?? null, $allowedSheets);

            $key = HospitalChatBotQna::questionKey((string) $item['question']);
            self::assertArrayNotHasKey($key, $keys, "중복 질문: {$item['question']}");
            $keys[$key] = true;

            $category = (string) $item['category'];
            $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
        }

        self::assertEqualsCanonicalizing([
            '병의원회원',
            '게시물',
            '상담신청',
            '의료진 프로필',
            '이벤트 등록',
            '충전금',
        ], array_keys($categoryCounts));
    }

    public function test_question_key_ignores_case_and_repeated_whitespace(): void
    {
        self::assertSame(
            HospitalChatBotQna::questionKey('비밀번호  변경 방법'),
            HospitalChatBotQna::questionKey(" 비밀번호\n변경 방법 "),
        );
    }
}
