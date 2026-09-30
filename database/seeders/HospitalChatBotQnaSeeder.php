<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQnaFolder;
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

            // 분류는 1단 폴더로 만든다. 등록 화면이 폴더를 고르게 되어 있으므로
            // 시드 데이터도 같은 구조로 들어가야 한다.
            $folder = HospitalChatBotQnaFolder::query()->firstOrCreate(
                ['parent_id' => null, 'name' => $category],
                ['depth' => 1, 'sort_order' => 0],
            );

            HospitalChatBotQna::withTrashed()->firstOrCreate(
                ['question_key' => HospitalChatBotQna::questionKey($question)],
                [
                    'folder_id' => $folder->id,
                    'category' => $category,
                    'question' => $question,
                    'answer' => $answer,
                    'status' => HospitalChatBotQna::STATUS_ACTIVE,
                    'sort_order' => (int) ($item['sort_order'] ?? 0),
                ],
            );
        }
    }
}
