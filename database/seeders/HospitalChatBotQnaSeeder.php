<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Queries\HospitalChatBotQnaTreeQuery;
use Illuminate\Database\Seeder;
use JsonException;
use RuntimeException;

final class HospitalChatBotQnaSeeder extends Seeder
{
    /**
     * @throws JsonException
     */
    public function run(): void
    {
        $path = database_path('seeders/data/hospital_chatbot_qnas.json');
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("초기 챗봇 Q&A 파일을 읽을 수 없습니다: {$path}");
        }

        $items = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($items)) {
            throw new RuntimeException('초기 챗봇 Q&A 데이터 형식이 올바르지 않습니다.');
        }

        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new RuntimeException('초기 챗봇 Q&A 항목 형식이 올바르지 않습니다.');
            }

            $category = trim((string) ($item['category'] ?? ''));
            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));

            if ($category === '' || $question === '' || $answer === '') {
                throw new RuntimeException('초기 챗봇 Q&A에 빈 분류·질문·답변이 있습니다.');
            }

            // 이전 형식으로 등록하거나 삭제한 질문도 다시 만들지 않는다.
            if (HospitalChatBotQna::withTrashed()->where('question_key', HospitalChatBotQna::questionKey($question))->exists()) {
                continue;
            }
            $parent = HospitalChatBotQna::withTrashed()->firstOrCreate(
                ['question_key' => HospitalChatBotQnaTreeQuery::questionKey($category, null)],
                ['parent_id' => null, 'question' => $category, 'answer' => '', 'category' => '', 'depth' => 1, 'sort_order' => 0],
            );
            if ($parent->trashed()) {
                continue;
            }

            HospitalChatBotQna::withTrashed()->firstOrCreate(
                ['question_key' => HospitalChatBotQnaTreeQuery::questionKey($question, $parent->id)],
                [
                    'parent_id' => $parent->id,
                    'depth' => 2,
                    'category' => '',
                    'question' => $question,
                    'answer' => $answer,
                    'status' => HospitalChatBotQna::STATUS_ACTIVE,
                    'sort_order' => (int) ($item['sort_order'] ?? 0),
                ],
            );
        }
    }
}
